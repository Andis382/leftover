@if (session('status'))
    <div class="notice ok" role="status">
        <x-icon name="check-circle" size="20" />
        <div>{{ session('status') }}</div>
    </div>
@endif

{{--
    A failed form takes focus to a summary that names each problem and links to
    the field it belongs to. The inline message stays on the field as well — a
    summary that replaces it just moves the problem somewhere less useful.
--}}
@if ($errors->any())
    <div class="summary" id="error-summary" role="alert" tabindex="-1">
        <h2>{{ trans_choice('ui.a11y.errors', $errors->count(), ['count' => $errors->count()]) }}</h2>
        <ul>
            @foreach ($errors->keys() as $key)
                <li><a href="#f-{{ str_replace(['[', ']', '.', '_'], ['-', '', '-', '-'], $key) }}">{{ $errors->first($key) }}</a></li>
            @endforeach
        </ul>
    </div>
    @once
        @push('scripts')
        <script>
        (function () {
          var summary = document.getElementById('error-summary');
          if (!summary) return;
          summary.focus();
          summary.addEventListener('click', function (event) {
            var link = event.target.closest('a[href^="#"]');
            if (!link) return;
            var field = document.getElementById(link.getAttribute('href').slice(1));
            if (field) { event.preventDefault(); field.focus(); }
          });
        })();
        </script>
        @endpush
    @endonce
@endif
