{{-- Laravel's header, without its laravel.com logo for an app named "Laravel": an email loads nothing from another site (S5). --}}
@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
{!! $slot !!}
</a>
</td>
</tr>
