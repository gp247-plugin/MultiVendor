@php
/*
* This template only use for MultiVendor
$layout_page = vendor_product_list
**Variables:**
- $products: paginate
Use paginate: $products->appends(request()->except(['page','_token']))->links()
*/ 
@endphp

@extends($GP247TemplatePath.'.layout')

{{-- block_main_content_center --}}
@section('block_main_content_center')

  @php
    $subPath = 'vendor_info';
    $view = gp247_plugin_process_view($appPath, $GP247TemplatePath, $subPath);
    gp247_check_view($view);
  @endphp

  @includeIf($view, ['store' => $store, 'stats' => $stats, 'storeCode' => $storeCode, 'tab' => 'products', 'storeId' => $storeId])
  {{-- Sort filter --}}
  <div class="product-top-panel group-md">

      {{-- Render pagination result --}}
      @include($GP247TemplatePath.'.common.pagination_result', ['items' => $products])
      {{--// Render pagination result --}}

      {{-- Render include filter sort --}}
      @php
          $view = gp247_shop_process_view($GP247TemplatePath, 'common.shop_product_filter_sort');
      @endphp
      @include($view, ['filterSort' => $filter_sort])
      {{--// Render include filter sort --}}

  </div>
  {{-- //Sort filter --}}

  {{-- Product list --}}
  <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
    @foreach ($products as $key => $product)
      {{-- Same product card the template's own lists use (Livewire, gp247/shop) --}}
      @livewire('gp247-shop-front::product-card', ['productId' => $product->id], key('mv-cat-card-'.$product->id))
    @endforeach
  </div>
  {{-- //Product list --}}

  {{-- Render pagination --}}
  @includeIf($GP247TemplatePath.'.common.pagination', ['items' => $products])
  {{--// Render pagination --}}

  
@endsection
{{-- //block_main_content_center --}}


@section('blockStoreLeft')
{{-- Categories tore --}}

@if (function_exists('gp247_vendor_get_categories_front') &&  count(gp247_vendor_get_categories_front($storeId)))
<div class="aside-item mb-6">
  <h6 class="mb-2 text-sm font-semibold uppercase tracking-wide text-gray-500">{{ gp247_language_render('front.categories_store') }}</h6>
  <ul class="space-y-1 text-sm">
    @foreach (gp247_vendor_get_categories_front($storeId) as $category)
    <li class="text-gray-700 hover:text-blue-600"><a href="{{ $category->getUrl() }}"> {{ $category->title }}</a></li>
    @endforeach
  </ul>
</div>
@endif
{{-- //Categories tore --}}
@endsection


@push('scripts')

@endpush

