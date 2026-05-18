@props([
    'title' => null,
    'size' => 12,
    'lat' => null,
    'lng' => null,
    'mapId' => 'map_' . uniqid(),
    'required' => true,
])

<div class="col-sm-{{ $size }}">
    <div class="form-group">
        <label>{{ $title }}</label>

        <div id="{{ $mapId }}" style="height: 400px"></div>

        <input type="hidden" name="lat" id="lat_{{ $mapId }}" value="{{ old('lat', $lat) }}">
        <input type="hidden" name="lng" id="lng_{{ $mapId }}" value="{{ old('lng', $lng) }}">

        @error('lat')
            <span class="text-danger">{{ $message }}</span>
        @enderror
        @error('lng')
            <span class="text-danger">{{ $message }}</span>
        @enderror
    </div>
</div>

@push('scripts')
    <script
        src="https://maps.googleapis.com/maps/api/js?key={{ \App\Models\Setting::where('name', 'map_key')->first()->value }}">
    </script>

    <script>
        const kuwaitBounds = {
            north: 30.1,
            south: 28.5,
            west: 46.5,
            east: 48.6,
        };
        document.addEventListener("DOMContentLoaded", function() {
            let lat = parseFloat("{{ old('lat', $lat ?? 29.3759) }}");
            let lng = parseFloat("{{ old('lng', $lng ?? 47.9774) }}");

            let map = new google.maps.Map(document.getElementById("{{ $mapId }}"), {
                zoom: 10,
                center: {
                    lat: lat,
                    lng: lng
                },
                restriction: {
                    latLngBounds: kuwaitBounds,
                    strictBounds: true,
                },
            });

            let marker = new google.maps.Marker({
                position: {
                    lat: lat,
                    lng: lng
                },
                map: map,
                draggable: true
            });
            if ("{{ old('lat', $lat) }}" && "{{ old('lng', $lng) }}") {
                map.setZoom(12);
                map.panTo(marker.getPosition());
            }

            function updateInputs(lat, lng) {
                document.getElementById("lat_{{ $mapId }}").value = lat.toFixed(7);
                document.getElementById("lng_{{ $mapId }}").value = lng.toFixed(7);
            }

            map.addListener("click", function(event) {
                marker.setPosition(event.latLng);
                updateInputs(event.latLng.lat(), event.latLng.lng());
            });

            marker.addListener("dragend", function(event) {
                updateInputs(event.latLng.lat(), event.latLng.lng());
            });
        });
    </script>
@endpush
