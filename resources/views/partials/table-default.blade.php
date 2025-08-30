
@props(['headers' => [], 'rows' => []])
<div class="card">
  <table>
    <thead>
      <tr>
        @foreach($headers as $h)
          <th>{{ $h }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      {{ $slot }}
    </tbody>
  </table>
</div>
