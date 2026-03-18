<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $photo->title }}
        </h2>
    </x-slot>

    <div style="max-width: 800px; margin: 0 auto;">

        <div style="display: flex; gap: 20px; margin-top: 20px;">
            <div>
                <p>元画像</p>
                <img src="{{ asset('storage/' . $photo->image_path) }}?v={{ uniqid() }}"
                    style="max-height: 800px; max-width: 100%; object-fit: contain;">
            </div>

            <div>
                <p>背景削除後</p>
                <img src="{{ asset('storage/' . $photo->processed_path) }}?v={{ uniqid() }}"
                    style="max-height: 800px; max-width: 100%; object-fit: contain;">

                <div style="margin-top: 10px; position: relative; z-index: 10; text-align: right;">
                    <a href="{{ asset('storage/' . $photo->processed_path) }}"
                        download="{{ $photo->title }}_processed.png"
                        style="background: blue; color: white; padding: 10px 20px; display: inline-block; font-size: 18px; border-radius: 6px;">
                        ダウンロード
                    </a>
                </div>
            </div>
        </div>

        <div style="margin-top: 20px;">
            <a href="{{ route('photos.index') }}"
                style="background: #6b7280; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
                一覧に戻る
            </a>
        </div>

    </div>
</x-app-layout>