@extends('layouts.app')

@section('title', 'Satscribe History – AI Bitcoin Describer')

@section('content')
    <h2>📜 Description History</h2>

    @if ($descriptions->isEmpty())
        <p>No descriptions found yet.</p>
    @else
        <ul>
            @foreach($descriptions as $desc)
                <li style="margin-bottom: 1rem;">
                    <strong>{{ ucfirst($desc->type) }}:</strong> {{ $desc->input }}<br>
                    {{ $desc->ai_response }}<br>
                    <small>{{ $desc->created_at->diffForHumans() }}</small>
                    <hr>
                </li>
            @endforeach
        </ul>

        <div class="pagination" style="margin-top: 2rem; text-align: center;">
            {{ $descriptions->links() }}
        </div>
    @endif
@endsection
