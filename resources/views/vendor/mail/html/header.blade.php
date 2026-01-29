@props(['url'])
<tr>
<td class="header" style=" padding: 30px 20px; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ asset('assets/image/logo-original.png') }}" class="logo" alt="{{ config('app.name') }} Logo" style="height: 50px; width: auto; max-width: 200px; filter: brightness(0) invert(1);">
</a>
</td>
</tr>
