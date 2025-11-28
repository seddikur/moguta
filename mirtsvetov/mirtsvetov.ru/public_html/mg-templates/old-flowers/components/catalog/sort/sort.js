"use strict"; ! function() {
    var t = document.getElementById("sorter"),
    e = document.getElementById("sort");
    t && e && (e.value = t.value, e.addEventListener("change", function(e) {
        t.value = e.target.value,
        document.forms.filter.submit()
    }))
} ();