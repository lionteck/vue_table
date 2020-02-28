var evento = "";
var app = Vue.component('operator-viewer', {
    template: '#operator-viewer',
    data: function() {
        return {
            evento: "",
            testo_sec: ""
        }

    },
    methods: {

    },
    created: function() {
        this.testo_sec = "pippo"
        console.log(window)
            //  console.log(evento)
    }
});