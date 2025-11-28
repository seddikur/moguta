var smallCartTemplate = document
  .querySelector(".smallCartRowTemplate")
  .content.querySelector("tr");
if (popup = document.querySelector(".popupCartRowTemplate")) {
  var popUpTemplate = popup.content.querySelector("tr");
}

// Заполнение корзины аяксом
$("body").on("click", ".js-add-to-cart", function (e) {
  var productId = $(this).data("item-id");
  transferEffect(productId, $(this), ".js-catalog-item");

  var request =
    "inCartProductId=" + $(this).data("item-id") + "&amount_input=1";
  if ($(this).parents(".js-product-form").length) {
    request = $(this).parents(".js-product-form").formSerialize();
    if (!$(".js-amount-wrap").length) {
      request += "&amount_input=1";
    }
  }

  $.ajax({
    type: "POST",
    url: mgBaseDir + "/cart",
    data: "updateCart=1&inCartProductId=" + productId + "&" + request,
    dataType: "json",
    cache: false,
    success: function (response) {
      $('.cart-informer').addClass('active');
      setTimeout(function() {
          $('.cart-informer').removeClass('active');
      }, 2000);
      if ("success" == response.status) {
        dataSmalCart = "";
        dataPopupCart = "";
        response.data.dataCart.forEach(printSmalCartData);

        $(".mg-desktop-cart .small-cart-table").html(dataSmalCart);

        if ($(".js-popup-cart-table").length) {
          $(".js-popup-cart-table").html(dataPopupCart);
        }

        $(".total .total-sum span").text(response.data.cart_price_wc);
        $(".pricesht").text(response.data.cart_price);
        $(".countsht").text(response.data.cart_count);
      }
    },
  });

  return false;
});

// строит содержимое маленькой и всплывающей корзины в выпадащем блоке
function printSmalCartData(element, index, array) {
  var html = $($.parseHTML("<table><tbody></tbody></table>"));
  html.find("tbody").html(smallCartTemplate.cloneNode(true));
  html
    .find(".js-smallCartImg")
    .attr("src", element.image_thumbs[30])
    .attr("alt", element.title)
    .attr("srcset", element.image_thumbs["2x30"] + " 2x");

  var prodUrl =
    mgBaseDir +
    "/" +
    (element.category_url || element.category_url == ""
      ? element.category_url
      : "catalog/") +
    element.product_url;
  html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
  html
    .find(".js-smallCartProdAnchor")
    .attr("href", prodUrl)
    .text(element.title);

  html.find(".js-smallCartProperty").html(element.property_html);
  html.find(".js-smallCartAmount").text(element.countInCart);
  html.find(".js-cartPrice").text(element.priceInCart);

  html
    .find(".js-delete-from-cart")
    .attr("data-delete-item-id", element.id)
    .attr("data-property", element.property)
    .attr("data-variant", element.variantId);

  window.dataSmalCart += html.find("tr:first").parent().html();

  if ($(".modal-cart .small-cart-table").length) {
    html = $(
      $.parseHTML(
        "<table><tbody></tbody></table>"
      )
    );

    html.find("tbody").html(smallCartTemplate.cloneNode(true));

    html.find(".js-smallCartImgAnchor").attr("href", prodUrl);
    html
      .find(".js-smallCartProdAnchor")
      .attr("href", prodUrl)
      .text(element.title);

    html
      .find(".js-smallCartImg")
      .attr("src", element.image_thumbs[30])
      .attr("alt", element.title)
      .attr("srcset", element.image_thumbs["2x30"] + " 2x");

    html.find(".js-smallCartProperty").html(element.property_html);
    html.find(".js-smallCartAmount").text(element.countInCart);
    html.find(".js-cartPrice").text(element.priceInCart);

    html
      .find(".js-delete-from-cart")
      .attr("data-delete-item-id", element.id)
      .attr("data-property", element.property)
      .attr("data-variant", element.variantId);

    dataPopupCart += html.find("tr:first").parent().html();
  }
}

// Эффект полёта товара в корзину
function transferEffect(productId, buttonClick, wrapperClass) {
  var $css = {
    height: "100%",
    opacity: 0.5,
    position: "relative",
    "z-index": 100,
  };

  var $transfer = {
    to: $(".icon-cart"),
    className: "transfer_class",
  };

  //если кнопка на которую нажали находится внутри нужного контейнера.
  if (
    buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]").length
  ) {
    // даем способность летать для картинок из слайдера новинок и прочих.
    var tempObj = buttonClick
      .parents(wrapperClass)
      .find("img[data-transfer=true][data-product-id=" + productId + "]");
    tempObj.effect("transfer", $transfer, 600);
    $(".transfer_class").html(tempObj.clone().css($css));
  } else {
    //Если кнопка находится не в контейнере, проверяем находится ли она на странице карточки товара.
    if ($(".product-details-image").length) {
      // даем способность летать для картинок из галереи в карточке товара.
      $(".product-details-image").each(function () {
        if ($(this).css("display") != "none") {
          $(this).find(".mg-product-image").effect("transfer", $transfer, 600);
          $(".transfer_class").html($(this).find("img").clone().css($css));
        }
      });
    } else {
      // даем способность летать для всех картинок.
      var tempObj = $(
        "img[data-transfer=true][data-product-id=" + productId + "]"
      );
      tempObj.effect("transfer", $transfer, 600);
    }
  }

  if (tempObj) {
    $(".transfer_class").html(tempObj.clone().css($css));
  }
}
