@extends('layout.master')
@section('title', 'Simple Test')
@section('content')
    <div style="padding: 100px; background: red; color: white; font-size: 48px; text-align: center; min-height: 500px;">
        <h1>IF YOU SEE THIS RED BOX, THE PAGE WORKS!</h1>
        <p style="font-size: 24px;">Time: {{ now() }}</p>
    </div>
@endsection
