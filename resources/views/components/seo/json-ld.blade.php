@props(['data' => null])

@if (filled($data))
    @php
        if (is_string($data)) {
            $json = $data;
        } else {
            $json = json_encode(
                $data,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG
            );
        }
    @endphp
    <script type="application/ld+json">{!! $json !!}</script>
@endif
