class Storage {
  constructor(url) {
    this.workUrl = url;
  }

  async sortRacks(e) {
    const items = e.to.children;
    const new_order = [];

    for (let i = 0; i < items.length; i++) {
      new_order.push(parseInt(items[i].dataset.id ?? 0, 10));
    }

    if (new_order.length === 0) return;

    let response = await fetch(this.workUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
      },
      body: JSON.stringify({
        new_order,
      }),
    });

    if (!response.ok) return console.error(response.status);
  }

  async moveBoxes(e) {
    const old_rack = e.from.parentNode.dataset.id ?? null;
    const new_rack = e.to.parentNode.dataset.id ?? null;
    const box = e.item.dataset.id ?? null;

    if (old_rack === null || new_rack === null || box === null) return;

    if (old_rack === new_rack) return;

    let response = await fetch(this.workUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json; charset=utf-8',
      },
      body: JSON.stringify({
        box,
        old_rack,
        new_rack,
      }),
    });

    if (!response.ok) return console.error(response.status);
  }
}

const storage = new Storage(whWorkUrl),
  storageRacks = document.querySelectorAll('.wh_racks'),
  storageBoxes = document.querySelectorAll('.wh_box_list');

storageRacks.forEach(function (el) {
  Sortable.create(el, {
    group: 'racks',
    animation: 500,
    handle: 'legend',
    draggable: 'fieldset',
    onSort: (e) => storage.sortRacks(e),
  });
});

storageBoxes.forEach(function (el) {
  Sortable.create(el, {
    group: 'boxes',
    animation: 500,
    handle: '.handle',
    draggable: '.drag_box',
    onAdd: (e) => storage.moveBoxes(e),
  });
});
