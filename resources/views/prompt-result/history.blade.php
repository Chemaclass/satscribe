@extends('layouts.app')

@section('title', 'Satscribe History – AI Bitcoin Describer')

@section('content')
    <section>
        <h2>📜 Description History</h2>

        @if ($descriptions->isEmpty())
            <p>No descriptions found yet.</p>
        @else
            <ul class="description-list">
                @foreach($descriptions as $desc)
                    <li class="description-entry">
                        <div class="description-header">
                            <strong>{{ ucfirst($desc->type) }}:</strong>
                            {{ $desc->input }}
                        </div>
                        <div class="description-body">
                            {!! Str::markdown($desc->ai_response) !!}
                        </div>
                        <div class="description-meta">
                            <small>{{ $desc->created_at->diffForHumans() }}</small>
                        </div>
                        <hr>
                    </li>
                @endforeach
            </ul>

            <div class="pagination">
                {{ $descriptions->links() }}
            </div>
        @endif
    </section>
@endsection
