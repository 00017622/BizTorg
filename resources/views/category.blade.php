@section('meta')
    <meta name="description" content="{{ Str::limit(strip_tags($category->name), 160) }}">
    <meta name="keywords" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta property="og:title" content="Категория: {{ $category->name }}">
    <meta property="og:description" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta property="og:image" content="{{ $category->image_url ? asset('storage/' . $category->image_url) : asset('default.png') }}">
    <meta property="og:url" content="{{ route('category.index', $category->slug) }}">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Категория: {{ $category->name }}">
    <meta name="twitter:description" content="Категория: {{ $category->name . ' - ' . $category->slug }}">
    <meta name="twitter:image" content="{{ $category->image_url ? asset('storage/' . $category->image_url) : asset('default.png') }}">
@endsection

@section('title', 'Категория: {{$category->name}} - {{$category->subcategory->name}}')
@extends('layouts.app')
@section('main')
@include('components.filters')

<section class="px-8">
@include('components.card')
</section>

<div class="pagination flex justify-center mt-8">
    {{ $products->appends(request()->query())->links('vendor.pagination.custom-pagination') }}
</div>
@endsection