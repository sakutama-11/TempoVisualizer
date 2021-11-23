<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TempoVisualizerController extends Controller
{
    public function analysis(string $music_file) {
        // Pythonのファイルがあるパスを設定
        $python_path =  "../app/Python/";
        $command = "python3 " . $python_path . "tempo_visualizer.py " . $music_file . " 2> error.log";
        exec($command , $outputs, $return_val);
        return $outputs;
    }
    public function post(Request $request) {
        $validated = $request->validate([
            'file' => 'required',
        ]);
        $original_name = $request->file('file')->getClientOriginalName();
        $filename = trim(mb_convert_kana($original_name, 'as', 'UTF-8'));
        $filename = preg_replace('/[^!-~]/', "", $filename);
        $request->file('file')->storeAs('public/music', $filename);
        $result = $this->analysis($filename);
        $image_file = "";
        if (!is_null($result)) {
            $image_file = $result[0];
        }
        return view('welcome', compact('filename', 'image_file', 'original_name'));
    }

    public function get(Request $request) {
        $files = Storage::allFiles('public/music/');
        foreach ($files as $f) {
            Storage::delete($f);
        }
        return view('welcome');
    }

    public function sample() {
        copy('sample.mp3', 'storage/music/sample.mp3');
        $original_name = "sample.mp3";
        $filename = $original_name;
        $result = $this->analysis($filename);
        $image_file = "";
        if (!is_null($result)) {
            $image_file = $result[0];
        }
        return view('welcome', compact('filename', 'image_file', 'original_name'));
    }
}
