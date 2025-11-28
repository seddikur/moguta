const cartAPI = {

    /**
     * 
     * Выполняет запрос к серверу с заданными параметрами.
     * 
     * @param {string} url - URL, на который отправляется запрос.
     * @param {string} method - HTTP-метод запроса (по умолчанию POST).
     * @param {Object|string} data - Данные, отправляемые на сервер.
     * 
     * @returns {Promise<Object>} - Возвращает результат ответа сервера.
     * 
     */
    sendRequest: async ({ url, method = 'POST', data }) => {
        try {
            const response = await $.ajax({ url, method, data, dataType: 'json', cache: false });
            return response;
        } catch (error) {
            console.error('Ошибка AJAX:', error);
            throw error;
        }
    },

    /**
     * 
     * Обновляет количество товара в корзине.
     * 
     * @param {Object} data - Данные для запроса.
     * 
     * @returns {Promise<Object>} - Результат ответа сервера.
     * 
     */
    update: (data) => cartAPI.sendRequest({
        url: `${mgBaseDir}/cart`,
        data: `updateCart=1&inCartProductId=${data.productId}&${data.request}`,
    }),

    /**
     * 
     * Удаляет товар из корзины.
     * 
     * @param {Object} data - Данные для запроса.
     * 
     * @returns {Promise<Object>} - Результат ответа сервера.
     * 
     */
    delete: (data) => cartAPI.sendRequest({
        url: `${mgBaseDir}/cart`,
        data: {
            action: 'cart',
            delFromCart: 1,
            itemId: data.productId,
            variantId: data.variantId,
        },
    }),

    /**
     * 
     * Обновляет количество товара в корзине.
     * 
     * @param {Object} data - Данные для запроса.
     * 
     * @returns {Promise<Object>} - Результат ответа сервера.
     * 
     */
    updateCartItem: (data) => cartAPI.sendRequest({
        url: 'ajax',
        data: {
            action: 'updateCartItem',
            actionerClass: 'Ajaxuser',
            productId: data.productId,
            variantId: data.variantId,
            count: data.count,
        }
    }),

    /**
     * 
     * Обновляет корзину товаров.
     * 
     * @param {Object} data - Данные для запроса.
     * 
     * @returns {Promise<Object>} - Результат ответа сервера.
     * 
     */
    refreshCartItems: (data) => cartAPI.sendRequest({
        url: `${mgBaseDir}/cart`,
        data: `refresh=1&count_change=1&${data.request}`
    })
};