{{-- 广告位：由广告模块填充，没有广告时不输出任何内容 --}}
@php($slotHtml = app()->bound('front.slot') ? app('front.slot')($slot, $current, $context ?? []) : '')
@if ($slotHtml !== '')
    <div class="slot slot-{{ $slot }}">{!! $slotHtml !!}</div>
@endif
