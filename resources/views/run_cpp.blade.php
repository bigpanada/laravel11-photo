<h1>Run C++ from Laravel</h1>

<form action="/run-cpp/exec" method="POST">
    @csrf
    <button type="submit">Run C++ Program</button>
</form>

@if (isset($command))
    <h2>Result</h2>
    <p><strong>Command:</strong> {{ $command }}</p>
    <p><strong>Return Code:</strong> {{ $return_code }}</p>

    <h3>Output:</h3>
    <pre>
@foreach ($output as $line)
{{ $line }}
@endforeach
    </pre>
@endif