<h2>📜 Description History</h2>
<ul>
    @foreach($descriptions as $desc)
        <li>
            <strong>{{ ucfirst($desc->type) }}:</strong> {{ $desc->input }}<br>
            {{ $desc->description }}<br>
            <small>{{ $desc->created_at->diffForHumans() }}</small>
            <hr>
        </li>
    @endforeach
</ul>

{{ $descriptions->links() }}
