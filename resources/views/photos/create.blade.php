<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            写真の新規登録
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <!-- 通常のアップロードフォーム -->
            <form action="/photos" method="POST" enctype="multipart/form-data">
                @csrf

                <p class="mb-4">
                    <label>タイトル：</label>
                    <input type="text" name="title" class="border p-1">
                </p>

                <p class="mb-4">
                    <label>画像ファイル：</label>
                    <input type="file" name="image" id="imageInput">
                </p>

                <!-- プレビュー表示 -->
                <img id="preview" class="mt-4 w-48 border hidden">

                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded">
                    保存
                </button>
            </form>

            <hr class="my-6">

            <!-- Webカメラ撮影 -->
            <h3 class="text-lg font-semibold mb-2">Webカメラ撮影</h3>

            <video id="camera" autoplay playsinline width="320" height="240" class="border"></video>
            <button id="capture" class="px-4 py-2 bg-green-500 text-white rounded mt-2">
                撮影
            </button>
            <canvas id="canvas" width="320" height="240" style="display:none;"></canvas>

            <form id="uploadForm" method="POST" action="/photos" enctype="multipart/form-data" class="mt-4">
                @csrf
                <input type="hidden" name="image" id="imageData">
            </form>
        </div>
    </div>

    <!-- 撮影中のローディング表示 -->
    <div id="loading" class="hidden text-gray-700 mt-4">
        <svg class="animate-spin h-6 w-6 inline-block" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8v4l3-3-3-3v4a8 8 0 100 16v-4l-3 3 3 3v-4a8 8 0 01-8-8z"></path>
        </svg>
        背景削除中...
    </div>

    <div style="margin-top: 20px;">
        <a href="{{ route('photos.index') }}"
            style="background: #6b7280; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
            一覧に戻る
        </a>
    </div>

    <script>
        navigator.mediaDevices.getUserMedia({
                video: true
            })
            .then(stream => {
                document.getElementById('camera').srcObject = stream;
            });


        // Webカメラ撮影
        document.getElementById('capture').onclick = () => {

            const captureBtn = document.getElementById('capture');
            const loading = document.getElementById('loading');
            const preview = document.getElementById('cameraPreview');
            const video = document.getElementById('camera');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');

            // ボタン無効化
            captureBtn.disabled = true;
            captureBtn.innerText = '処理中...';

            // ローディング表示
            loading.classList.remove('hidden');

            // 撮影
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            const data = canvas.toDataURL('image/jpeg');

            // Webカメラ用プレビューに表示
            //preview.src = data;
            //preview.classList.remove('hidden');

            // Webカメラ停止
            const stream = video.srcObject;
            stream.getTracks().forEach(track => track.stop());

            // hidden input にセット
            document.getElementById('imageData').value = data;

            // 自動送信
            document.getElementById('uploadForm').submit();
        };

        // ファイル選択プレビュー
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(event) {
                const img = document.getElementById('preview');
                img.src = event.target.result;
                img.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });
    </script>
</x-app-layout>