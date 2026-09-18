@extends('layouts.app')
@section('title', $product->name)
@section('pageclass', 'narrow')

@section('content')
<div class="page-head">
    <span class="micro">{{ __('ui.products.title') }}</span>
    <h1>{{ $product->name }}</h1>
</div>

<form method="post" action="{{ route('products.update', $product) }}" class="sheet">
    @csrf @method('PUT')

    <x-field name="name" :label="__('ui.products.name')" :value="$product->name" required />

    <div class="cols2">
        <x-field name="unit" control="select" :label="__('ui.products.unit')" :selected="$product->unit"
                 :options="collect(\App\Models\Product::UNITS)->mapWithKeys(fn ($u) => [$u => __('product.unit.'.$u)])->all()" />
        <x-field name="category" control="select" :label="__('ui.products.category')" :selected="$product->category"
                 :options="collect(\App\Models\Product::CATEGORIES)->mapWithKeys(fn ($c) => [$c => __('product.category.'.$c)])->all()" />
    </div>

    <div class="cols2">
        <x-field name="typical_batch" type="number" :label="__('ui.products.typical_batch')"
                 :value="$product->typical_batch" min="0" max="9999" inputmode="numeric" />
        <x-field name="round_to" type="number" :label="__('ui.products.round_to')"
                 :value="$product->round_to" min="1" max="100" inputmode="numeric" required />
    </div>
    <p class="hint">{{ __('ui.products.round_hint') }}</p>

    <div class="cols2">
        <x-field name="price" type="number" :label="__('ui.products.price')" :value="$product->price"
                 step="0.01" min="0" inputmode="decimal" />
        <x-field name="cost" type="number" :label="__('ui.products.cost')" :value="$product->cost"
                 step="0.01" min="0" inputmode="decimal" />
    </div>
    <p class="hint">{{ __('ui.products.cost_hint') }}</p>

    <label class="check">
        <input type="checkbox" name="active" value="1" @checked(old('active', $product->active))>
        <span class="what">{{ __('ui.products.active') }}</span>
    </label>

    <button class="btn block"><x-icon name="check" size="18" />{{ __('ui.products.save') }}</button>
    <a class="btn ghost block" href="{{ route('products.index') }}" style="margin-top:var(--s-2)">{{ __('ui.common.cancel') }}</a>
</form>

<details class="more">
    <summary style="color:var(--danger)"><x-icon name="trash" size="18" />{{ __('ui.products.remove') }}</summary>
    <div class="inner">
        <p class="small">{{ __('ui.products.retire_note') }}</p>
        <form method="post" action="{{ route('products.destroy', $product) }}">
            @csrf @method('DELETE')
            <button class="btn danger block">{{ __('ui.products.remove') }}</button>
        </form>
    </div>
</details>
@endsection
