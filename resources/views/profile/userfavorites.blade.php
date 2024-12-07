@extends('layouts.profile')

@section('content')
@include('components.card', ['products' => $favorites])
@endsection