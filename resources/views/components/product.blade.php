<div class="product-item">
    <div class="product-image">
        <img src="{{$product->image}}" width="250" height="250"/>
        <a href="{{route('products.show',$product->slug)}}" class="view-details">تفاصيل المنتج</a>
        <a href="{{route('favourites.show',$product)}}" class=" {{$product->favourites->first()?'wished-product':'wish-this-product'}}">
            <i class="flaticon-heart"></i>
        </a>
        @if($product->discount>0)
        <span class="discount">-{{(int)$product->discount}}%</span>
            @endif
    </div>
    <!-- End Product Image -->
    <!-- Start Product Content -->
    <div class="product-content">
        <h4 class="title"><a href="{{route('products.show',$product->slug)}}">{{$product->name}}</a></h4>
        @if($product->discount>0)
        <div class="product-details-price">
            <p class="price">{{$product->grand_price}} <span class="currency">{{__(getCurrency())}}</span></p>
            <p class="price-discount">{{$product->price}} {{__(getCurrency())}}</p>
        </div>
        @else
        <p class="price">{{$product->price}} <span class="currency">{{__(getCurrency())}}</span></p>
        @endif
        {!! Form::hidden('product_id',1,['id'=>'quantity-'.$product->id]) !!}
        <button class="add-to-cart" onclick="addToCart(this)" data-product="{{$product->id}}" data-price="{{$product->price}}">
            <i class="flaticon-shopping-cart"></i>
            <span class="btn-txt">أضف للسلة</span>
        </button>
    </div>
    <!-- End Product Content -->
</div>
