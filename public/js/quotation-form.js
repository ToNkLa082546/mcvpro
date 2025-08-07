// File: /public/js/quotation-form.js

(function () {
    // --- 1. ตรวจสอบว่าอยู่ในหน้าฟอร์มจริงหรือไม่ ---
    const itemsContainer = document.getElementById('items-container');
    if (!itemsContainer) {
        return; // ถ้าไม่ใช่หน้าฟอร์ม ให้หยุดทำงานทันที
    }

    // --- 2. ประกาศตัวแปรและผูก Event Listeners ---
    const addItemBtn = document.getElementById('add-item-btn');
    const quotationForm = document.getElementById('quotation-form');

    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
            addNewItemRow();
            updateItemNumbers();
            calculateTotals();
        });
    }

    itemsContainer.addEventListener('click', e => {
            if (e.target.closest('.remove-item-btn')) {
                e.target.closest('tr').remove();
                updateItemNumbers();
                calculateTotals();
                return;
            }
            
            const addSubItemBtn = e.target.closest('.add-subitem-btn');
            if (addSubItemBtn) {
                const parentRow = addSubItemBtn.closest('tr');
                addNewSubItemRow(parentRow);
                updateItemNumbers();
                calculateTotals();
                return;
            }

            const parentCell = e.target.closest('td');
            if (!parentCell) return;

            if (e.target.closest('.add-description-btn')) {
                parentCell.querySelector('.item-description').style.display = 'block';
                parentCell.querySelector('.remove-description-btn').style.display = 'inline-block';
                e.target.closest('.add-description-btn').style.display = 'none';
            }

            if (e.target.closest('.remove-description-btn')) {
                const descriptionTextarea = parentCell.querySelector('.item-description');
                descriptionTextarea.value = '';
                descriptionTextarea.style.display = 'none';
                parentCell.querySelector('.add-description-btn').style.display = 'inline-block';
                e.target.closest('.remove-description-btn').style.display = 'none';
            }
        });

    itemsContainer.addEventListener('change', e => {
            if (e.target.matches('.item-type')) {
                const row = e.target.closest('tr');
                if (e.target.value === 'hide') {
                    row.classList.add('hidden-in-quote');
                } else {
                    row.classList.remove('hidden-in-quote');
                } 
                calculateTotals();
            }
        });

        itemsContainer.addEventListener('input', e => {
            if (e.target.matches('.item-cost, .item-margin, .item-quantity')) {
                calculateTotals();
            }
        });

    new Sortable(itemsContainer, {
        animation: 150,
        handle: '.drag-handle',
        onEnd: updateItemNumbers,
    });
    const galleryModal = document.getElementById('galleryModal');
if (galleryModal) {
    // เมื่อ Modal ถูกเปิด ให้ไปดึงข้อมูลไฟล์มาแสดง
    galleryModal.addEventListener('show.bs.modal', function () {
        const galleryBody = document.getElementById('gallery-body');
        galleryBody.innerHTML = 'Loading...';
        fetch('/mcvpro/public/files/getGallery')
            .then(response => response.text())
            .then(html => {
                galleryBody.innerHTML = html;
            });
    });

    // เมื่อกดปุ่ม Attach Selected
    const attachBtn = document.getElementById('attach-selected-files');
        attachBtn.addEventListener('click', function() {
            const attachmentInputsContainer = document.getElementById('attachment-inputs');
            const attachmentsPreview = document.getElementById('attachments-preview');
            attachmentInputsContainer.innerHTML = ''; // ล้างของเก่า
            attachmentsPreview.innerHTML = ''; // ล้างของเก่า

            // หาไฟล์ทั้งหมดที่ถูกเลือก (มี class 'selected')
            document.querySelectorAll('#gallery-body .file-item.selected').forEach(item => {
                const fileId = item.dataset.fileId;
                const fileName = item.dataset.fileName;
                
                // สร้าง hidden input เพื่อส่ง ID ไปกับฟอร์ม
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'attachment_ids[]';
                input.value = fileId;
                attachmentInputsContainer.appendChild(input);

                // แสดงชื่อไฟล์ที่เลือก
                attachmentsPreview.innerHTML += `<span class="badge bg-light text-dark me-1">${fileName}</span>`;
            });

            // ปิด Modal
            bootstrap.Modal.getInstance(galleryModal).hide();
        });
    }


    // --- 3. ฟังก์ชัน Helper ทั้งหมด ---
    // (นำฟังก์ชันทั้งหมดของคุณมาวางไว้ในนี้)
    
    function populateFormWithData() {
    console.log("1. ✅ ฟังก์ชัน populateFormWithData เริ่มทำงาน");
    const dataElement = document.getElementById('quotation-data');
    const itemsContainer = document.getElementById('items-container');

    if (!dataElement || !dataElement.textContent.trim()) {
        console.warn("2. ❌ ไม่พบ Element #quotation-data หรือไม่มีข้อมูลข้างใน");
        addNewItemRow();
        calculateTotals();
        updateItemNumbers();
        return;
    }

    let data;
    try {
        data = JSON.parse(dataElement.textContent);
        console.log("3. ✅ แปลงข้อมูล JSON สำเร็จ:", data);
    } catch (e) {
        console.error("3. ❌ เกิด Error ตอนแปลง JSON:", e);
        console.error("ข้อมูล JSON ที่มีปัญหา:", dataElement.textContent);
        return; // หยุดทำงานถ้า JSON ผิด
    }

    itemsContainer.innerHTML = '';

    const items = data.items || [];
    console.log("4. ℹ️ ข้อมูล Items ที่จะนำไปสร้าง:", items);

    if (items.length > 0) {
        items.forEach((item, index) => {
            console.log(`5. -> กำลังสร้างแถวที่ ${index + 1}`, item);
            addNewItemRow(item);
        });
    } else {
        console.warn("4a. ❌ ไม่พบรายการสินค้า (items array is empty)");
        addNewItemRow();
    }

    calculateTotals();
    updateItemNumbers();
    console.log("6. ✅ เติมข้อมูลลงฟอร์มเสร็จสิ้น");
}

    function addNewItemRow(itemData = {}) {
    const itemsContainer = document.getElementById('items-container');
    const tempId = 'temp_id_' + Date.now();
    const row = document.createElement('tr');
    row.dataset.tempId = tempId;

    // สังเกตการใช้ itemData.xxx ใน value="..." เพื่อเติมข้อมูล
    row.innerHTML = `
        <td class="item-no text-center" style="vertical-align: middle;"></td>
        <td>
            <input type="text" name="items[item_name][]" class="form-control item-name" required placeholder="Item Name" 
                   value="${itemData.item_name || ''}">
            <textarea name="items[description][]" class="form-control item-description mt-1" rows="2" placeholder="Description..." style="display: none;">${itemData.description || ''}</textarea>
            <button type="button" class="btn btn-outline-danger btn-sm remove-description-btn" style="display: none;">&times; Remove Description</button>
            <button type="button" class="btn btn-outline-success btn-sm add-description-btn">+ Description</button>
            <button type="button" class="btn btn-outline-success btn-sm add-subitem-btn mt-1" title="Add Sub-item">+ Sub</button>
            <input type="hidden" name="items[temp_id][]" value="${tempId}">
            <input type="hidden" name="items[is_subitem][]" value="0">
            <input type="hidden" name="items[parent_temp_id][]" value="">
        </td>
        <td>
            <select name="items[type][]" class="form-select form-select-sm item-type">
                <option value="show" ${itemData.type === 'show' ? 'selected' : ''}>Show</option>
                <option value="hide" ${itemData.type === 'hide' ? 'selected' : ''}>Hide</option>
            </select>
        </td>
        <td><input type="number" name="items[cost][]" class="form-control item-cost" step="0.01" min="0" required value="${itemData.cost || '0'}"></td>
        <td><input type="number" name="items[margin][]" class="form-control item-margin" step="0.1" min="0" required value="${itemData.margin || '20'}"></td>
        <td><input type="text" class="form-control-plaintext text-end item-price" readonly></td>
        <td><input type="number" name="items[quantity][]" class="form-control item-quantity" min="1" required value="${itemData.quantity || '1'}"></td>
        <td><input type="text" class="form-control-plaintext text-end item-total" readonly></td>
        <td class="text-center" style="vertical-align: middle;"><button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="fas fa-trash"></i></button></td>
    `;
    itemsContainer.appendChild(row);

    // ถ้ามี description ให้แสดง textarea
    if (itemData.description) {
        const descTextarea = row.querySelector('.item-description');
        if (descTextarea) descTextarea.style.display = 'block';
    }

    return row;
}

    function addNewSubItemRow(parentRow, subItemData = {}) {
    const row = document.createElement('tr');
    row.classList.add('sub-item-row');
    const parentTempId = parentRow.dataset.tempId;

    row.innerHTML = `
        <td class="item-no text-center" style="vertical-align: middle;"></td>
        <td style="background-color: #f8f9fa;">
            <div class="d-flex align-items-center">
                <span class="sub-item-number me-2 fw-bold"></span>
                <input type="text" name="items[item_name][]" class="form-control form-control-sm item-name" required placeholder="Sub-item name" value="${subItemData.item_name || ''}">
            </div>
            <div class="ps-4">
                <textarea name="items[description][]" class="form-control form-control-sm item-description mt-1" rows="1" placeholder="Description..." style="display: ${subItemData.description ? 'block' : 'none'};">${subItemData.description || ''}</textarea>
                <button type="button" class="btn btn-outline-danger btn-sm remove-description-btn" style="display: ${subItemData.description ? 'inline-block' : 'none'};">&times; Remove Description</button>
                <button type="button" class="btn btn-outline-success btn-sm add-description-btn" style="display: ${subItemData.description ? 'none' : 'inline-block'};">+ Description</button>
            </div>
            <input type="hidden" name="items[temp_id][]" value="${subItemData.temp_id || ''}">
            <input type="hidden" name="items[is_subitem][]" value="1">
            <input type="hidden" name="items[parent_temp_id][]" value="${parentTempId}">
        </td>
        <td>
            <select name="items[type][]" class="form-select form-select-sm item-type">
                <option value="show" ${subItemData.type === 'show' ? 'selected' : ''}>Show</option>
                <option value="hide" ${subItemData.type === 'hide' ? 'selected' : ''}>Hide</option>
            </select>
        </td>
        <td><input type="number" name="items[cost][]" class="form-control form-control-sm item-cost" step="0.01" min="0" value="${subItemData.cost || '0'}" required></td>
        <td><input type="number" name="items[margin][]" class="form-control form-control-sm item-margin" step="0.1" min="0" value="${subItemData.margin || '20'}" required></td>
        <td><input type="text" class="form-control-plaintext form-control-sm text-end item-price" value="0.00" readonly></td>
        <td><input type="number" name="items[quantity][]" class="form-control form-control-sm item-quantity" min="1" value="${subItemData.quantity || '1'}" required></td>
        <td><input type="text" class="form-control-plaintext form-control-sm text-end item-total" value="0.00" readonly></td>
        <td class="text-center" style="vertical-align: middle;"><button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="fas fa-trash"></i></button></td>
    `;

    parentRow.insertAdjacentElement('afterend', row);
    return row;
}

    function calculateTotals() {
        let subTotal = 0;
        const subTotalInput = document.getElementById('sub-total');
        const vatAmountInput = document.getElementById('vat-amount');
        const grandTotalInput = document.getElementById('grand-total');
        const VAT_RATE = 0.07;

        itemsContainer.querySelectorAll('tr').forEach(row => {
            const costInput = row.querySelector('.item-cost');
            const marginInput = row.querySelector('.item-margin');
            const quantityInput = row.querySelector('.item-quantity');

            // ข้ามไปถ้าแถวนั้นไม่มี input ราคา
            if (!costInput || !marginInput || !quantityInput) return;

            // คำนวณราคาทุกแถว โดยไม่สนใจ Type
            const cost = parseFloat(costInput.value) || 0;
            const margin = parseFloat(marginInput.value) || 0;
            const quantity = parseInt(quantityInput.value) || 0;
            
            const pricePerUnit = cost * (1 + (margin / 100));
            const itemTotal = pricePerUnit * quantity;

            row.querySelector('.item-price').value = pricePerUnit.toFixed(2);
            row.querySelector('.item-total').value = itemTotal.toFixed(2);
            
            // บวกยอดรวมของทุกแถว
            subTotal += itemTotal;
        });

        const vatAmount = subTotal * VAT_RATE;
        const grandTotal = subTotal + vatAmount;

        if (subTotalInput) subTotalInput.value = subTotal.toFixed(2);
        if (vatAmountInput) vatAmountInput.value = vatAmount.toFixed(2);
        if (grandTotalInput) grandTotalInput.value = grandTotal.toFixed(2);
    }

    function updateItemNumbers() {
        const rows = itemsContainer.querySelectorAll('tr');
        let mainCounter = 0;
        let subCounter = 0;

        rows.forEach(row => {
            const numberCell = row.querySelector('td:first-child');
            if (!numberCell) return;

            // จัดการไอคอนลากและเลขลำดับ
            if (row.classList.contains('sub-item-row')) {
                subCounter++;
                const numberSpan = row.querySelector('.sub-item-number');
                if (numberSpan) {
                    numberSpan.textContent = `${mainCounter}.${subCounter}`;
                }
                numberCell.innerHTML = `<i class="fas fa-grip-vertical drag-handle" style="cursor: grab; color: #ced4da;"></i>`;
            } else {
                mainCounter++;
                subCounter = 0;
                numberCell.innerHTML = `<i class="fas fa-grip-vertical drag-handle" style="cursor: grab; color: #ced4da;"></i><span class="ms-2">${mainCounter}</span>`;
            }
        });
    }

    function transformFlatItemsToNested(flatItems) {
    const itemMap = {};
    const nestedItems = [];

    flatItems.forEach(item => {
        item.children = [];
        itemMap[item.item_id] = item;
    });

    flatItems.forEach(item => {
        if (item.parent_item_id) {
            const parent = itemMap[item.parent_item_id];
            if (parent) {
                parent.children.push(item);
            }
        } else {
            nestedItems.push(item);
        }
    });

    return nestedItems;
}
    

    // --- 4. เริ่มต้นการทำงาน ---
    // เรียกใช้ฟังก์ชันหลักเพื่อเติมข้อมูลทันที
    populateFormWithData();

})(); // วงเล็บสุดท้ายนี้คือการสั่งให้ฟังก์ชันทั้งหมดทำงาน