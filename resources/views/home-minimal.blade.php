@extends('layout.master')
@section('title', 'Get a USA Tax-Free Shipping Address | Ship to All Caribbean Islands | 2-4 Days Shipping | Marketsz')
@section('content')
    @include('includes.main-header')
    
    <div style="padding: 50px; background: #f0f0f0; min-height: 500px;">
        <h1 style="color: red; font-size: 24px;">MINIMAL TEST PAGE</h1>
        <p>If you see this, the basic structure works.</p>
        <p>Current time: {{ now() }}</p>
    </div>
@endsection

@section('script')
    <script>
        console.log('Script section loaded successfully');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
        });
    </script>
@endsection
