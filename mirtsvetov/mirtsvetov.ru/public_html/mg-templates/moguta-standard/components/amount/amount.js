$(document).ready(function () {
  let amountWrap = '.js-amount-wrap',
    amountInput = '.js-amount-input';

  // проводит все проверки введенного количества (num)
  // запрещается вписывать не кратное минимальному количеству
  // округляем в меньшую сторону, до допустимого кратнго значения (minAmount)
  // не больше чем есть на складе
  function prepareAmount(num, minAmount, maxAmount) {
    //console.log(num+','+ minAmount+','+  maxAmount);
    if (isNaN(num)) {
      num = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (isNaN(minAmount)) {
      minAmount = 0;
    }
    if (num > 0 && num < minAmount) {
      num = minAmount;
    }
    if (num <= 0) {
      num = minAmount;
    }
    if (num >= maxAmount) {
      num = maxAmount;
    }

    // если значение в поле больше минимально допустимого,
    // то вычисляем сколько раз в это значение входит минимально допустимое
    if (num > minAmount) {
      // точность после запятой
      let fix = 0;
      let str = minAmount.toString();
      if (str) {
        let val = str.split('.');
        if (val && val.length == 2) {
          fix = val[1].length;
        }
      }
      let count = (num / minAmount).toFixed(fix);
      count = Math.floor(count);
      num = (count * minAmount).toFixed(fix);
    }
    return num;
  }

  //увеличение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $('body').on('click', '.js-amount-change-up', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val + minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  //уменьшение количества товара (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('click', '.js-amount-change-down', function () {
    let obj = $(this).parents(amountWrap).find(amountInput);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    val = val - minAmount;
    val = prepareAmount(val, minAmount, maxAmount);
    obj.val(val).trigger('change');
    return false;
  });

  // Исключение ввода в поле выбора количества недопустимых значений. (страница товара, миникарточка, корзина, страница заказа)
  $(document.body).on('change', amountInput, function () {
    let obj = $(this);
    let val = 1 * obj.val();
    let minAmount = obj.data('increment-count') != 0 ? obj.data('increment-count') : 1;
    let maxAmount = obj.data('max-count')>0?obj.data('max-count'):9999999;

    obj.val(prepareAmount(val, minAmount, maxAmount));
    return false;
  });

  const amountInputs = document.querySelectorAll(amountInput);
  for (const inputAmount of amountInputs) {
    inputAmount.addEventListener('keydown', (e) => {
      if (e.keyCode === 13) {
        e.preventDefault();
        let obj = e.target;
        let val = 1 * obj.value;
        let minAmount = obj.dataset.incrementCount != 0 ? obj.dataset.incrementCount : 1;
        let maxAmount = obj.dataset.maxCount > 0 ? obj.dataset.maxCount :9999999;

        obj.value = prepareAmount(val, minAmount, maxAmount);
        return false;
      }
    });
  }
});
