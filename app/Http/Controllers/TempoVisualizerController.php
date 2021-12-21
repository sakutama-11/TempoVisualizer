<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class TempoVisualizerController extends Controller
{
    public function analysis(string $music_file) {
        // Pythonのファイルがあるパスを設定
        $python_path =  "../app/Python/";
        $command = "python3 " . $python_path . "tempo_visualizer.py " . $music_file . " 2> error.log";
        exec($command , $outputs, $return_val);
        return $outputs;
    }
    public function analysisAPI(string $music_file, string $key) {
        $input = json_encode([
                "filename" => $music_file,
                "key" => $key
        ]);
        $response = Http::post(env('AWS_API_URI'), [
            'input' => $input,
            'stateMachineArn' => env('AWS_STATEMACHINE_ARN')
        ]);
        $body = json_decode($response->body());
        $execArn = $body->{'executionArn'};
        $arnArr = explode(":", $execArn);
        $execId = end($arnArr);
        return $execId;
    }

    public function post(Request $request) {
        try {
            $validated = $request->validate([
                'file' => 'required',
            ]);
            $original_name = $request->file('file')->getClientOriginalName();
            $filename = trim(mb_convert_kana($original_name, 'as', 'UTF-8'));
            $filename = preg_replace('/[^!-~]/', "", $filename);
            $filepath = 'public/music';
            $file = $request->file('file');
            $file->storeAs($filepath, $filename);
            $key = Storage::disk('s3')->putFile($filepath, $file);

            $result = $this->analysisAPI($filename, $key);
            return redirect()->route('result', ['execId' => $result, 'filename'=> $filename]);
        }
        catch (\Exception $exception) {
            $message = "Analysis failed. Please try again...";
            return view('index', compact('message'));
        }
    }

    public function get(Request $request) {
        return view('index');
    }

    public function delete(Request $request) {

        $files = Storage::allFiles('public/music/');
        foreach ($files as $f) {
            Storage::delete($f);
        }
        return redirect('/');
    }
    public function result(Request $request, string $execId) {
        $filename = $request->query('filename');
        $status = $this->checkStatus($execId);
        switch ($status) {
            case 'RUNNING':
                return view('wait', compact('filename'));
            case 'SUCCEEDED':
                break;
            default:
                $message = "Analysis failed. Please try again...";
                return view('index', compact('message'));
        }
        $image_file = $this->music2png($filename);
        $filepath = 'public/img/';
        $image = Storage::disk('s3')->get($filepath . $image_file);
        Storage::disk('local')->put($filepath . $image_file, $image);
        $original_name = $filename;
        return view('result', compact('filename', 'image_file', 'original_name'));
    }

    public function sample() {
        try {
            copy('sample.wav', 'storage/music/sample.wav');
            $original_name = "sample.wav";
            $filename = $original_name;
            $filepath = 'public/music';
            $key = Storage::disk('s3')->putFile($filepath, 'storage/music/sample.wav');

            $result = $this->analysisAPI($filename, $key);
            return redirect()->route('result', ['execId' => $result, 'filename'=> $filename]);
        }
        catch (\Exception $exception) {
            dd($exception);
            $message = "Analysis failed. Please try again...";
            return view('index', compact('message'));
        }
    }

    private function checkStatus(string $execId) {
        $response = Http::post(env('AWS_API_URI') . '/stetus', [
            'executionArn' => env('AWS_EXECUTION_ARN') . $execId
        ]);
        $body = json_decode($response->body());
        return $body->{'status'};
    }
    private function music2png(string $music_filename) {
        $filename = explode(".", $music_filename);
        array_pop($filename);
        array_push($filename, 'png');
        $image_file = implode('.', $filename);
        return $image_file;
    }
}
