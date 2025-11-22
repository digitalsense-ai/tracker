
@props(['items' => []])
<div class="toolbar">
  @foreach($items as $i)
    <x-pill :label="$i['label']" :value="$i['value']" :type="$i['type'] ?? 'info'" />
  @endforeach
</div>
