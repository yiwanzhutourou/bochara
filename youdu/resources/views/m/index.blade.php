@extends('layout')
@section('content')
    <div id="app"></div>
@endsection

@section('script')
    <script src="{{ mix('/dist/m/index.js') }}"></script>
@endsection