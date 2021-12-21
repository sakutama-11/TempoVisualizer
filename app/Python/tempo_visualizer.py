import os
import sys
os.environ[ 'NUMBA_CACHE_DIR' ] = '/tmp/'
import librosa as lr
import numpy as np
from pathlib import Path
import cv2
import colorsys
import math


def avg_tempo(arr_sec, idx, range):
  avg_tempo = 60 / (( arr_sec[idx + range] - arr_sec[idx] ) / range )
  return int(avg_tempo)

def analyze_tempo(music_filename):
    path = Path('storage/music/'+music_filename)
    y, sr = lr.load(path)
    tempo, beats = lr.beat.beat_track(y=y, sr=sr)
    return tempo, beats

def spline_by_mean_between_frames(tempo, beats, avg_range):
    # フレーム間の秒数を配列にする
    beat_time = lr.frames_to_time(beats)
    # 平均テンポの配列の0項目に加える
    arr_tempo = []
    avg_range = 4
    for i in range(len(beat_time) - avg_range):
      arr_tempo = np.append(arr_tempo, avg_tempo(beat_time, i, avg_range))

    # 最初に2フレーム、最後に2フレーム平均テンポを加える(beats配列とサイズが同じになるはず)
    arr_tempo = np.append([tempo, tempo], arr_tempo)
    arr_tempo = np.append(arr_tempo, [tempo, tempo])
    return arr_tempo, beat_time

def create_image_from_tempo(beats, beat_time, tempo, arr_tempo):
    height = len(beats) * 4 // 10
    width = len(beats) * 4
    src = np.zeros((height, width, 3))
    for i in range(len(beat_time) - 1):
      # 塗りつぶす幅を計算
      start = round(beat_time[i] * width / beat_time[-1])
      pixel = round((beat_time[i+1] - beat_time[i]) * width / beat_time[-1])
      # arr_tempoとtmpoの差に応じて色相を指定
      # bpm差 1で8色相変化
      hue = (tempo - arr_tempo[i]) * -6 + 60
      hue = max(hue, 0)
      hue = min(240, hue)
      hue = float(hue) / 180
      rgb_0_to_1 = colorsys.hsv_to_rgb(hue, 1, 1)
      rgb = [n*255 for n in rgb_0_to_1]
      # 塗りつぶす
      src[:, start:start+pixel, 0:3] = rgb

    # 画像作る
    img = cv2.cvtColor(src.astype(np.float32), cv2.COLOR_BGR2RGB)
    return img

if __name__ == '__main__':
    music_filename = sys.argv[1]
    image_filename = os.path.splitext(music_filename)[0]+'.png'

    tempo, beats = analyze_tempo(music_filename)

    arr_tempo, beat_time = spline_by_mean_between_frames(tempo, beats, 4)

    img = create_image_from_tempo(beats, beat_time, tempo, arr_tempo)
    ret = cv2.imwrite('./storage/image/' + image_filename,img)
    assert ret,'failed'
    print(tempo)
