<x-app-layout>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="font-size: 24px; font-weight: bold;">写真一覧</h2>

        <a href="{{ route('photos.create') }}"
            style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none;">
            新規登録
        </a>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mt-6">

        @foreach ($photos as $photo)
        <div class="py-6 border-b border-gray-300">

            <h3 class="text-lg font-semibold mb-2">
                <a href="{{ route('photos.show', $photo->id) }}">
                    {{ $photo->title }}
                </a>
            </h3>

            <div class="flex flex-row gap-6 items-start">

                {{-- 元画像 --}}
                <div>
                    <p class="text-sm text-gray-600">元画像</p>
                    <img src="{{ asset('storage/' . $photo->image_path) }}?v={{ uniqid() }}" class="w-48">
                </div>

                {{-- 背景削除後 --}}
                <div>
                    <p class="text-sm text-gray-600">背景削除後</p>
                    <img src="{{ asset('storage/' . $photo->processed_path) }}?v={{ uniqid() }}" class="w-48">
                </div>

                {{-- 削除ボタン --}}
                <form action="{{ route('photos.destroy', $photo->id) }}" method="POST"
                    onsubmit="return confirm('削除しますか？');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-red-600 hover:underline">削除</button>
                </form>

            </div>
        </div>
        @endforeach

    </div>
</x-app-layout>