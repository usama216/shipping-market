@extends('layout.master')
@section('title', 'Test Page')
@section('content')
    @include('includes.main-header')
    
    <div style="padding: 50px; background: #f0f0f0; min-height: 500px; color: black;">
        <h1 style="color: red; font-size: 32px;">SIMPLE TEST PAGE</h1>
        <p style="font-size: 18px;">If you see this, the basic structure works.</p>
        <p>Current time: {{ now() }}</p>
        <p>Asset test: <img src="{{ asset('assets/images/cloud-1.svg') }}" alt="test" style="width: 50px;" /></p>
    </div>
@endsection

@section('script')
    <script>
        console.log('Script section loaded');
    </script>
@endsection
