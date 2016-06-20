// These might not be a good idea. I'm just lazy.
// Hopefully they will not completely break down if some browser implements these functions.

Element.prototype.setAttributes = function(attributes) {
    for (let key in attributes) {
        if (!attributes.hasOwnProperty(key)) {
            return true;
        }

        this.setAttribute(key, attributes[key]);
    }
};

Element.prototype.appendBefore = function(elm) {
    elm.parentNode.insertBefore(this, elm);
};

Element.prototype.appendAfter = function(elm) {
    elm.parentNode.insertBefore(this, elm.nextSibling);
};

Element.prototype.toggle = function() {
    if (window.getComputedStyle(this).display !== 'none') {
        this.hide();
    } else {
        this.show();
    }
};

Element.prototype.hide = function() {
    this.style.display = 'none';
};

Element.prototype.show = function(style = 'block') {
    this.style.display = style;
};

Element.prototype.insertAtCaret = function(before, after = '') {
    if (document.selection) {
        // IE
        let selection = document.selection.createRange();
        selection.text = before + selection.text + after;
        this.focus();
    } else if (this.selectionStart || this.selectionStart === 0) {
        // FF & Chrome
        let selectedText = this.value.substr(this.selectionStart, (this.selectionEnd - this.selectionStart));
        let startPos = this.selectionStart;
        let endPos = this.selectionEnd;
        this.value = this.value.substr(0, startPos) + before + selectedText + after + this.value.substr(endPos,
                this.value.length);

        // Move selection to end of "before" -tag
        this.selectionStart = startPos + before.length;
        this.selectionEnd = startPos + before.length;

        this.focus();
    } else {
        // Nothing selected/not supported, append
        this.value += before + after;
        this.focus();
    }
};

NodeList.prototype.toggle = function() {
    this.forEach(function(elm) {
        elm.toggle();
    });
};

NodeList.prototype.hide = function() {
    this.forEach(function(elm) {
        elm.hide();
    });
};

NodeList.prototype.show = function() {
    this.forEach(function(elm) {
        elm.show();
    });
};

NodeList.prototype.remove = function() {
    this.forEach(function(elm) {
        elm.remove();
    });
};
