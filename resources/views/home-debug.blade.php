@extends('layout.master')
@section('title', 'Debug Test')
@section('content')
    {{-- Test without main-header first --}}
    <div style="padding: 50px; background: yellow; min-height: 500px; color: black; font-size: 24px;">
        <h1 style="color: red;">DEBUG TEST PAGE</h1>
        <p>If you see this yellow box, the page is loading!</p>
        <p>Time: {{ now() }}</p>
    </div>
    
    {{-- Test with hero section include --}}
    @include('sections.hero-section')
    
    <div style="padding: 50px; background: lightblue; min-height: 200px; color: black;">
        <p>If you see this blue box after the hero, the hero section loaded!</p>
    </div>
@endsection

@section('script')
    <script>
        console.log('=== DEBUG: Script section loaded ===');
        document.addEventListener('DOMContentLoaded', function() {
            console.log('=== DEBUG: DOM Content Loaded ===');
        });
    </script>
@endsection
