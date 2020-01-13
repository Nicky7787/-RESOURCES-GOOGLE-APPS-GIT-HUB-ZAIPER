(function (window) {
    "use strict";

    window.ls.container.set('alerts', function (window) {
        return {
            list: [],
            counter: 0,
            add: function (message, time) {
                var scope = this;

                message.id = this.counter++;

                scope.list.unshift(message);

                if (time > 0) { // When 0 alert is unlimited in time
                    window.setTimeout(function (message) {
                        return function () {
                            scope.remove(message.id)
                        }
                    }(message), time);
                }

                return message.id;
            },
            remove: function (id) {
                let scope = this;

                for (let index = 0; index < scope.list.length; index++) {
                    let obj = scope.list[index];

                    if (obj.id === parseInt(id)) {
                        if (typeof obj.callback === "function") {
                            obj.callback();
                        }

                        scope.list.splice(index, 1);
                    };
                }
            }
        };
    }, true, true);
})(window);