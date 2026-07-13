{{-- Flat view: every document as its own card, in a responsive grid. --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-3 sm:gap-4">
    @foreach($documents as $doc)
        @include('citizen.documents._doc-card', ['doc' => $doc, 'showAuction' => true])
    @endforeach
</div>
