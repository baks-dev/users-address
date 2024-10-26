/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

addressLength = 10;

dataUserAddress = null;

geoModal = null;


function initAdddress()
{
    setTimeout(function initAdddress()
    {

        dataUserAddress = document.querySelectorAll('[data-address]');

        if(dataUserAddress)
        {

            dataUserAddress.forEach(function(area)
            {
                if(area.tagName === 'SELECT')
                {
                    /** Присваиваем адрес по выбору из списка */
                    area.addEventListener("change", (event) =>
                    {

                        document.querySelector('[data-latitude]').value = area.options[area.selectedIndex].dataset.lati;
                        document.querySelector('[data-longitude]').value = area.options[area.selectedIndex].dataset.longi;

                        //document.querySelector('[data-geocode]').value = area.options[area.selectedIndex].dataset.value;
                    });
                } else
                {
                    /** Присваиваем адрес по карте */
                    area.addEventListener('focus', geocodeAddress);
                }


                //setTimeout(function initGeocodeAddress() {

                //if (typeof geocodeAddress.debounce === 'function') {

                //area.addEventListener('input', geocodeAddress.debounce(2000));
                //area.addEventListener('blur', geocodeAddress);


                //setTimeout(initGeocodeAddress, 100);

                //}, 100);

            });


            return;
        }
        setTimeout(initAdddress, 100);

    }, 100);
}

initAdddress();

function geocodeAddress()
{


    /* Создаём объект класса XMLHttpRequest */
    const request = new XMLHttpRequest();

    /*  Получаем из ссылки адрес запроса */
    //let url = '/geocode/01882e34-acaa-7a3a-80e8-a8326cb34799';

    let address = this.value;
    address = address.replace('/', '-')
    let url = '/geocode/' + address;

    /* Указываем метод соединения GET и путь к файлу на сервере */
    request.open('GET', url);
    /* Указываем заголовки для сервера */
    //request.setRequestHeader('Content-Type', 'application/x-www-form-url');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');


    /* Получаем ответ от сервера на запрос*/
    request.addEventListener("readystatechange", function(evemnt)
    {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(request.readyState === 4 && request.status === 200)
        {

            let modal = document.getElementById('modal_address');

            if(!modal)
            {
                modal = document.getElementById('modal');
            }

            modal.innerHTML = request.responseText;

            geoModal = new bootstrap.Modal(modal, {
                keyboard: false
            })


            geoModal.show();

            /* делаем глобальную отметку о завершении запроса */
            //eventEmitter.dispatchEvent(new Event('complete'));

            /* Сбрасываем содержимое модального окна при закрытии */
            modal.addEventListener('hidden.bs.modal', function(event)
            {
                this.innerHTML = '';
            })

            // /* Если в модальном окне присутствует select2 */
            // modal.querySelectorAll('[data-select="select2"]').forEach(function (item) {
            //     new NiceSelect(item, {searchable: true});
            // });


            modal.addEventListener('shown.bs.modal', function(event)
            {

                modal.querySelectorAll('[data-address]').forEach(function(area)
                {
                    if(typeof replaceGeocodeAddress.debounce === 'function')
                    {
                        area.addEventListener('input', function(event)
                        {

                            /** Блокируем кнопку и показываем прелоад */
                            modal.querySelectorAll('.spinner-border').forEach(function(indicator)
                            {
                                btn = indicator.closest('button');

                                indicator.classList.remove('d-none');
                                btn.disabled = true;
                                btn.type = 'button';
                            });


                        });

                        replaceGeocodeAddress();

                        area.addEventListener('input', replaceGeocodeAddress.debounce(2000));
                    }
                });


                let repeat = 100;
                setTimeout(function UMzLVLSAMe()
                {

                    if(repeat >= 1000)
                    { return; }

                    if(typeof ymaps === 'object')
                    {
                        /** Инициируем карту */
                        ymaps.ready(init);
                    }

                    repeat = repeat * 2;
                    setTimeout(UMzLVLSAMe, repeat);

                }, 100);


                modal.querySelectorAll('form').forEach(function(forms)
                {

                    /*/!* событие отправки формы *!/*/
                    forms.addEventListener('submit', function(event)
                    {
                        event.preventDefault();
                        geoModal.hide();
                        return false;
                    });
                });
            });


            if($html)
            {
                $html = request.responseText;
            }


        } else
        {
            /* Закрываем модальное окно */
            //let myModalEl = document.querySelector('#modal')
            //let modal = bootstrap.Modal.getOrCreateInstance(myModalEl) // Returns a Bootstrap modal instance
            //modal.hide();
        }
    });

    /*Выполняем запрос*/
    request.send();

    return false;
}


function replaceGeocodeAddress()
{

    if(typeof this.value == 'undefined' || this.value.length < addressLength)
    {
        return;
    }

    const request = new XMLHttpRequest();

    let address = this.value;
    address = address.replace('/', '-');
    let url = '/geocode/' + address;


    /* Указываем метод соединения GET и путь к файлу на сервере */
    request.open('GET', url);
    /* Указываем заголовки для сервера */
    //request.setRequestHeader('Content-Type', 'application/x-www-form-url');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');


    /* Получаем ответ от сервера на запрос*/
    request.addEventListener("readystatechange", function(evemnt)
    {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if(request.readyState === 4 && request.status === 200)
        {

            let modal = document.getElementById('modal_address');

            if(!modal)
            {
                modal = document.getElementById('modal');
            }

            modal.innerHTML = request.responseText;

            // /* Сбрасываем содержимое модального окна при закрытии */
            // modal.addEventListener('hidden.bs.modal', function (event) {
            //     this.innerHTML = '';
            // })

            // /* Если в модальном окне присутствует select2 */
            // modal.querySelectorAll('[data-select="select2"]').forEach(function (item) {
            //     new NiceSelect(item, {searchable: true});
            // });

            modal.querySelectorAll('[data-address]').forEach(function(area)
            {

                if(typeof replaceGeocodeAddress.debounce === 'function')
                {

                    area.addEventListener('input', function(event)
                    {
                        /** Блокируем кнопку и показываем прелоад */
                        modal.querySelectorAll('.spinner-border').forEach(function(indicator)
                        {
                            btn = indicator.closest('button');

                            indicator.classList.remove('d-none');
                            btn.disabled = true;
                            btn.type = 'button';
                        });
                    });

                    area.addEventListener('input', replaceGeocodeAddress.debounce(2000));
                }

                /** Инициируем карту */
                ymaps.ready(init);

            });


            modal.querySelectorAll('form').forEach(function(forms)
            {

                /* событие отправки формы */
                forms.addEventListener('submit', function(event)
                {

                    console.log();

                    event.preventDefault();

                    submitAddressForm(forms);

                    //geoModal.hide();


                    return false;
                });
            });


            if($html)
            {
                $html = request.responseText;
            }


        } else
        {
            /* Закрываем модальное окно */
            //let myModalEl = document.querySelector('#modal')
            //let modal = bootstrap.Modal.getOrCreateInstance(myModalEl) // Returns a Bootstrap modal instance
            //modal.hide();
        }
    });

    /*Выполняем запрос*/
    request.send();

    return false;

}


function init()
{

    let geocode = document.getElementById('user_address_form_desc');
    let latitude = document.getElementById('user_address_form_latitude');
    let longitude = document.getElementById('user_address_form_longitude');

    myMap = new ymaps.Map("map", {
        center: [latitude.value, longitude.value],
        controls: ['routeButtonControl', 'fullscreenControl'],
        zoom: geocode.value ? 17 : 6
    });

    if(geocode.value)
    {
        /** Метка на карте */
        myMap.geoObjects.add(new ymaps.Placemark([latitude.value, longitude.value], {
            balloonContent: geocode.value,
            iconCaption: geocode.value
        }, {
            preset: 'islands',
            iconColor: '#4684D0'
        }));
    }

}


async function submitAddressForm(forms)
{

    const data = new FormData(forms);

    // /* показываем индикатор */
    let indicator = forms.querySelector('.spinner-border');

    if(indicator)
    {
        btn = indicator.closest('button');

        indicator.classList.remove('d-none');
        btn.disabled = true;
        btn.type = 'button';
    }

    await fetch(forms.action, {
        method: forms.method, // *GET, POST, PUT, DELETE, etc.
        //mode: 'same-origin', // no-cors, *cors, same-origin
        cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
        credentials: 'same-origin', // include, *same-origin, omit
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        redirect: 'follow', // manual, *follow, error
        referrerPolicy: 'no-referrer', // no-referrer, *no-referrer-when-downgrade, origin, origin-when-cross-origin, same-origin, strict-origin, strict-origin-when-cross-origin, unsafe-url
        body: data // body data type must match "Content-Type" header
    })

        .then((response) =>
        {

            closeProgress();
            btn.type = 'submit';

            const contentType = response.headers.get('content-type');
            if(!contentType || !contentType.includes('application/json'))
            {

                $errorFormHandler = '{ "type":"danger" , ' +
                    '"header":"Ошибка"  , ' +
                    '"message" : "Возникла ошибка при заполнении" }';

                createToast(JSON.parse($errorFormHandler));


                throw new TypeError("Oops, we haven't got JSON!");
            }

            /* Закрываем модальное окно */
            //let myModalEl = document.querySelector('#modal')
            //let modal = bootstrap.Modal.getOrCreateInstance(myModalEl) // Returns a Bootstrap modal instance
            geoModal.hide();

            if(response.status === 302)
            {
                window.location.href = '/refresh';
                return;
            }

            return response.json();
        })

        .then((data) =>
        {


            if(data === undefined)
            {

                return false;
            }

            if(data.status === 200 && data.redirect !== undefined)
            {
                window.location.href = data.redirect;

                return false;
            }

            createToast(data);

            if(data.status !== 200)
            {
                return;
            }

            changeAddress(forms);

        });


    return false;


    // .catch((error) => {
    //     console.error('Error:', error);
    // }); // parses JSON response into native JavaScript objects
}


function changeAddress(forms)
{

    // user_address_form

    if(forms !== false && forms.name === 'user_address_form')
    {
        /** Меняем кнопку submit */
        let btn = forms.querySelector('button[type="submit"]');

        var inputs = forms.elements;

        for(i = 0; i < inputs.length; i++)
        {

            /** Заполняем описание */
            if(inputs[i].id === 'user_address_form_desc')
            {

                document.querySelectorAll('[data-address]').forEach(function(area)
                {
                    area.value = inputs[i].value;
                });
            }


            /** Заполянем аддрес */
            if(inputs[i].id === 'user_address_form_address')
            {

                let dataGeocode = document.querySelector('[data-geocode]');

                if(dataGeocode)
                {
                    dataGeocode.value = inputs[i].value;
                }

            }

            /** Заполянем Широту */
            if(inputs[i].id === 'user_address_form_latitude')
            {

                let dataLatitude = document.querySelector('[data-latitude]');

                if(dataLatitude)
                {
                    dataLatitude.value = inputs[i].value;
                }
            }

            /** Заполянем Долготу */
            if(inputs[i].id === 'user_address_form_longitude')
            {


                let dataLongitude = document.querySelector('[data-longitude]');

                if(dataLongitude)
                {
                    dataLongitude.value = inputs[i].value;
                }

            }
        }


        delete (forms);

        //return false;

        //forms.action = '/basket';

    }
}