var baseurl = "https://" + document.location.host + "/wp-admin/admin-ajax.php";

Vue.component('operator-viewer-cart', {
    template: '#operator-viewer-cart',
    data: function() {
        return {
            date_id: null,
            table_datas: null,
            table_datas_filtered: null,
            table_headers: null,
            table_header_values: null,
            filter: {}
        }

    },
    methods: {
        callApi: function(url, data) {
            return new Promise(function(resolve, reject) {
                axios({
                    method: 'get',
                    url: url
                }).then((response) => {
                    resolve(response.data);
                }).catch(error => {
                    reject(error)
                });
            })
        },
        changeFilter: function(event) {
            /* var filter = event.currentTarget.getAttribute("filter");
             console.log(filter);*/
            var array_filtered = this.table_datas;
            const _this = this;
            Object.keys(this.filter).forEach(key => {
                if (_this.filter[key] != "0") {
                    array_filtered = array_filtered.filter(function(value) {
                        console.log(value[key]);
                        console.log(_this.filter[key])
                        return ((value[key] == null ? "" : value[key]) == _this.filter[key]);
                    })
                }
            });
            this.table_datas_filtered = array_filtered;
        },
        loadTable: function(event) {
            let cat_prom = this.callApi(baseurl + "?action="+action+"&date_id=" + this.date_id, {});
            const _this = this;
            cat_prom.then((success) => {
                console.log(success)
                success.table_headers.forEach(element => {
                    console.log(element)
                    _this.filter[element] = "0";
                });
                _this.table_datas = success.table_datas;
                _this.table_headers = success.table_headers;
                _this.table_datas_filtered = success.table_datas;
                _this.table_header_values = success.values;
            })


        },
        loadChart: function(){
            let cat_prom = this.callApi(baseurl + "?action=chart_vendite", {});
            const _this = this;
            cat_prom.then((success) => {
                Vue.nextTick(function () {
                    var ctx = document.getElementById('myChart').getContext('2d');
                    var chart = new Chart(ctx, {
                        // The type of chart we want to create
                        type: 'line',
                       
                        // The data for our dataset
                        data: success,

                        // Configuration options go here
                        options: {}
                    });
                });
            });
        }
    },
    created: function() {
        this.loadChart();
    }
})