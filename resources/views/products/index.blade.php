@extends('layouts.app')
@section('title', __('ui.products.title'))

@section('content')
<div class="page-head">
    <span class="micro">{{ $user->displayName() }}</span>
    <h1>{{ __('ui.products.title') }}</h1>
    <p>{{ __('ui.products.subtitle') }}</p>
</div>

<div class="sheet flush">
    <div class="rows">
        @forelse ($products as $product)
            <div class="entry">
                <span class="glyph"><x-icon :name="$product->icon()" size="19" /></span>
                <span class="entry-body">
                    <span class="entry-title">{{ $product->name }}</span>
                    <span class="entry-sub">
                        {{ $product->categoryLabel() }}
                        @if ($product->price) · {{ $user->money((float) $product->price) }} {{ __('product.unit.'.$product->unit) }} @endif
                        @if ($product->round_to > 1) · ×{{ $product->round_to }} @endif
                    </span>
                </span>
                <span class="entry-side">
                    @unless ($product->active)
                        <x-tag icon="archive">{{ __('ui.products.retired') }}</x-tag>
                    @endunless
                    <a class="linkbtn" href="{{ route('products.edit', $product) }}">{{ __('ui.products.edit') }}</a>
                </span>
            </div>
        @empty
            <div class="empty">
                <x-icon name="bread" size="40" />
                <p>{{ __('ui.products.none') }}</p>
            </div>
        @endforelse
    </div>
</div>

<h2>{{ __('ui.products.add') }}</h2>
<form method="post" action="{{ route('products.store') }}" class="sheet">
    @csrf
    <x-field name="name" :label="__('ui.products.name')" required autocomplete="off" />

    <div class="cols2">
        <x-field name="unit" control="select" :label="__('ui.products.unit')" selected="piece"
                 :options="collect(\App\Models\Product::UNITS)->mapWithKeys(fn ($u) => [$u => __('product.unit.'.$u)])->all()" />
        <x-field name="category" control="select" :label="__('ui.products.category')" selected="bread"
                 :options="collect(\App\Models\Product::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __('product.category.'.$c)])->all()" />
    </div>

    <div class="cols2">
        <x-field name="typical_batch" type="number" :label="__('ui.products.typical_batch')" min="0" max="9999" inputmode="numeric" />
        <x-field name="price" type="number" :label="__('ui.products.price')" step="0.01" min="0" inputmode="decimal" />
    </div>
    <p class="hint">{{ __('ui.products.price_hint') }}</p>

    <details class="more">
        <summary>{{ __('ui.products.cost') }} · {{ __('ui.products.round_to') }}</summary>
        <div class="inner">
            <x-field name="cost" type="number" :label="__('ui.products.cost')" :hint="__('ui.products.cost_hint')"
                     step="0.01" min="0" inputmode="decimal" />
            <x-field name="round_to" type="number" :label="__('ui.products.round_to')" :hint="__('ui.products.round_hint')"
                     value="1" min="1" max="100" inputmode="numeric" required />
        </div>
    </details>

    <button class="btn block"><x-icon name="plus" size="18" />{{ __('ui.products.add') }}</button>
</form>

<p class="small faint">{{ __('ui.products.retire_note') }}</p>
@endsection
