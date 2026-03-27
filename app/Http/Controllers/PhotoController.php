<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;
use Carbon\Carbon;

class PhotoController extends Controller
{
    public function index()
    {
        $photos = Photo::all();
        return view('photos.index', compact('photos'));
    }

    public function create()
    {
        return view('photos.create');
    }

    public function store(Request $request)
    {
        $photo = new Photo();

        // 通常アップロード
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('photos', 'public');
            $photo->image_path = $path;
        }

        // Webカメラ（Base64）
        if ($request->image && str_starts_with($request->image, 'data:image')) {
            $data = explode(',', $request->image)[1];
            $binary = base64_decode($data);
            $filename = 'photos/' . uniqid() . '.jpg';
            \Storage::disk('public')->put($filename, $binary);
            $photo->image_path = $filename;
        }

        // 1. タイトルが入力されていればそれを使う
        
        // タイトル自動設定ロジック
        if ($request->filled('title')) {
            $photo->title = $request->title;
        } else {
            if ($request->hasFile('image')) {
                $filename = $request->file('image')->getClientOriginalName();
                $photo->title = pathinfo($filename, PATHINFO_FILENAME);
            } else {
                $photo->title = 'webcam_' . now()->format('Ymd_His');
            }
        }
 
        // 保存
        $photo->save();

        // 保存後に C++ removebg を自動実行
        $input = storage_path('app/public/' . $photo->image_path);
        $output = storage_path('app/public/processed/' . $photo->id . '.png');

        $lib = "/var/www/html/cpp/onnxruntime-linux-x64-1.17.0/lib";
        $cmd = "LD_LIBRARY_PATH={$lib} /var/www/html/cpp/removebg {$input} {$output}";

        // ★ 同期実行（& を付けない）
        exec($cmd . " >> /var/www/html/storage/logs/removebg.log 2>&1", $outputLines, $returnCode);

        // C++ が正常終了したら processed=1
        if ($returnCode === 0) {
            $photo->processed_path = 'processed/' . $photo->id . '.png';
            $photo->processed = 1;
            $photo->save();
        }

        return redirect('/photos');
    }

    public function show(Photo $photo)
    {
        return view('photos.show', compact('photo'));
    }

    public function destroy(Photo $photo)
    {
        if ($photo->image_path && \Storage::disk('public')->exists($photo->image_path)) {
            \Storage::disk('public')->delete($photo->image_path);
        }

        if ($photo->processed_path && \Storage::disk('public')->exists($photo->processed_path)) {
            \Storage::disk('public')->delete($photo->processed_path);
        }

        $photo->delete();

        return redirect('/photos')->with('success', '削除しました');
    }
}
