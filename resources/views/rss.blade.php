<?= '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL ?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title><![CDATA[ BizTorgUz ]]></title>
        <link>https://068b-95-214-210-191.ngrok-free.app</link>
        <description><![CDATA[ Latest updates on our website ]]></description>
        <language>ru</language>
        <pubDate>{{ now()->toRfc2822String() }}</pubDate>
        <atom:link href="https://068b-95-214-210-191.ngrok-free.app/rss" rel="self" type="application/rss+xml"/>

        @foreach($products as $product)
            @php
                
                $googleMapUrl = $product->latitude && $product->longitude 
                    ? "https://www.google.com/maps?q={$product->latitude},{$product->longitude}" 
                    : null;

                $yandexMapUrl = $product->latitude && $product->longitude 
                    ? "https://yandex.ru/maps/?ll={$product->longitude},{$product->latitude}&z=17&l=map" 
                    : null;
            @endphp

            <item>
                <title><![CDATA[{{ $product->name }}]]></title>
                <link>https://068b-95-214-210-191.ngrok-free.app/obyavlenie/{{ $product->slug }}</link>
                <guid isPermaLink="true">https://068b-95-214-210-191.ngrok-free.app/obyavlenie/{{ $product->slug }}</guid>
                <pubDate>{{ $product->created_at->toRfc2822String() }}</pubDate>

                <description><![CDATA[
                    📢 Объявление: {{$product->name}}

                    📝 Описание: {{$product->description}}

                    💰 Цена: {{$product->price}} {{$product->currency == 'доллар' ? 'y.e' : 'сум'}}
                
                    🌍 Регион: {{$product->region->parent->name}}, {{$product->region->name}}
                
                    👤 Контактное лицо: {{$product->user->name}}
                
                    📞 Номер телефона: {{$product->user->profile->phone}}
                
                    @if ($googleMapUrl && $yandexMapUrl)
                    🗺️ Карта (Google): {{ $googleMapUrl }}
                
                    🗺️ Карта (Yandex): {{ $yandexMapUrl }}
                    @endif
                ]]></description>
                

                @if ($product->images->first())
                    @php
                        $imagePath = public_path('storage/' . str_replace('\\', '/', $product->images->first()->image_url));
                        $imageUrl = asset('storage/' . str_replace('\\', '/', $product->images->first()->image_url));
                        $mimeType = file_exists($imagePath) ? mime_content_type($imagePath) : 'image/jpeg';
                        $fileSize = file_exists($imagePath) ? filesize($imagePath) : 0;
                    @endphp
                    <enclosure 
                        url="{{ $imageUrl }}" 
                        type="{{ $mimeType }}" 
                        length="{{ $fileSize }}" 
                    />
                @endif
            </item>
        @endforeach
    </channel>
</rss>
