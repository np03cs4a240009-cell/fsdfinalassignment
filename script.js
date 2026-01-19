document.getElementById('search').addEventListener('keyup', function () {
fetch('ajax_search.php?q=' + this.value)
.then(res => res.text())
.then(data => document.getElementById('resu.js
lts').innerHTML = data);
});