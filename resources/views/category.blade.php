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