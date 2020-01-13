(function(window) {
  "use strict";

  window.ls.container.get("view").add({
    selector: "data-forms-code",
    controller: function(element, alerts) {
      let lang = element.dataset["formsCode"] || "json";
      let div = document.createElement("div");
      let pre = document.createElement("pre");
      let code = document.createElement("code");
      let copy = document.createElement("i");

      div.appendChild(pre);
      div.appendChild(copy);
      pre.appendChild(code);

      element.parentNode.appendChild(div);

      div.className = "ide";
      pre.className = "line-numbers";
      code.className = "prism language-" + lang;
      copy.className = "icon-docs copy";

      copy.title = "Copy to Clipboard";

      copy.addEventListener("click", function() {
        element.disabled = false;

        element.focus();
        element.select();

        document.execCommand("Copy");

        if (document.selection) {
          document.selection.empty();
        } else if (window.getSelection) {
          window.getSelection().removeAllRanges();
        }

        element.disabled = true;

        alerts.add({ text: "Copied to clipboard", class: "" }, 3000);
      });

      let check = function() {
        if (!element.value) {
          return;
        }

        let value = null;

        try {
          value = JSON.stringify(JSON.parse(element.value), null, 4);
        } catch (error) {
          value = element.value;
        }

        code.innerHTML = value;

        Prism.highlightElement(code);

        div.scrollTop = 0;
      };

      element.addEventListener("change", check);

      check();
    }
  });
})(window);
