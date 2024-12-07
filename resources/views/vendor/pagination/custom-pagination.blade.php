@if($paginator->hasPages())
<nav aria-label="Page navigation example">
    <ul class="flex items-center -space-x-px h-10 text-base">
    
      @if($paginator->onFirstPage())
      <li>
        <span class="flex items-center justify-center px-5 h-10 ms-0 leading-tight text-gray-300 bg-gray-800 border border-gray-700 rounded-s-lg hover:bg-gray-700 hover:text-white">
          <span class="sr-only">Previous</span>
          <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
          </svg>
        </span>
      </li>
      @else
      <li>
        <a href="{{ $paginator->previousPageUrl() }}" class="flex items-center justify-center px-5 h-10 ms-0 leading-tight text-gray-300 bg-gray-800 border border-gray-700 rounded-s-lg hover:bg-gray-700 hover:text-white">
            <span class="sr-only">Previous</span>
            <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
            </svg>
          </a>
      </li>
      @endif

      @foreach ($elements as $element)
          @if (is_string($element))
          <li>
              <span class="flex items-center justify-center px-5 h-10 leading-tight text-gray-300 bg-gray-800 border border-gray-700">{{ $element }}</span>
          </li>
          @endif

          @if (is_array($element))
            @foreach ($element as $page => $url)
            @if ($page === $paginator->currentPage())
            <li>
                <span class="z-10 flex items-center justify-center px-5 h-10 leading-tight text-white border border-gray-700 bg-gray-900 font-bold">{{ $page }}</span>
            </li>
            @else
            <li>
                <a href="{{ $url }}" class="flex items-center justify-center px-5 h-10 leading-tight text-gray-300 bg-gray-800 border border-gray-700 hover:bg-gray-700 hover:text-white">{{ $page }}</a>
            </li>
            @endif
            @endforeach
          @endif
      @endforeach

      @if ($paginator->hasMorePages())
      <li>
        <a href="{{ $paginator->nextPageUrl() }}" class="flex items-center justify-center px-5 h-10 leading-tight text-gray-300 bg-gray-800 border border-gray-700 rounded-e-lg hover:bg-gray-700 hover:text-white">
          <span class="sr-only">Next</span>
          <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
          </svg>
        </a>
      </li>
      @else
      <li>
        <span class="flex items-center justify-center px-5 h-10 leading-tight text-gray-300 bg-gray-800 border border-gray-700 rounded-e-lg">
          <span class="sr-only">Next</span>
          <svg class="w-3.5 h-3.5 rtl:rotate-180" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
          </svg>
        </span>
      </li>
      @endif
    </ul>
</nav>
@endif
