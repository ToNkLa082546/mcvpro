document.addEventListener('DOMContentLoaded', function () {

    // --- 1. ประกาศตัวแปรทั้งหมด ---
    const customerSelect = document.getElementById('customer_id');
    const projectSelect = document.getElementById('project_id');
    const addItemBtn = document.getElementById('add-item-btn');
    const itemsContainer = document.getElementById('items-container');
    const quotationForm = document.getElementById('quotation-form');
    const nextStepBtn = document.getElementById('next-step-btn');

    // --- 2. ฟังก์ชันทั้งหมด ---

    /**
     * รันลำดับเลขของรายการและรายการย่อยใหม่ทั้งหมด
     */
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

    /**
     * คำนวณยอดรวมทั้งหมด
     */
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

    /**
     * สร้างแถวสำหรับ "รายการหลัก" และคืนค่าแถวนั้นกลับไป
     */
    function addNewItemRow(itemData = {}) {
        const tempId = 'temp_id_' + Date.now();
        const row = document.createElement('tr');
        row.dataset.tempId = tempId;

        // ถ้า type เป็น 'hide' ให้เพิ่ม class เพื่อให้แถวจางลง
        if (itemData.type === 'hide') {
            row.classList.add('hidden-in-quote');
        }

        row.innerHTML = `
            <td class="item-no text-center" style="vertical-align: middle;"></td>
            <td>
                <input type="text" name="items[item_name][]" class="form-control item-name" required placeholder="Item Name" 
                    value="${itemData.item_name || ''}">
                <textarea name="items[description][]" class="form-control item-description mt-1" rows="2" placeholder="Description..." 
                        style="display: ${itemData.description ? 'block' : 'none'};">${itemData.description || ''}</textarea>
                <button type="button" class="btn btn-outline-danger btn-sm remove-description-btn" style="display: ${itemData.description ? 'inline-block' : 'none'};">&times; Remove Description</button>
                <button type="button" class="btn btn-outline-success btn-sm add-description-btn" style="display: ${itemData.description ? 'none' : 'inline-block'};">+ Description</button>
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
        return row;
    }

    /**
     * สร้างแถวสำหรับ "รายการย่อย" และคืนค่าแถวนั้นกลับไป
     */
    function addNewSubItemRow(parentRow, subItemData = {}) {
        const row = document.createElement('tr');
        row.classList.add('sub-item-row');
        const parentTempId = parentRow.dataset.tempId;

        if (subItemData.type === 'hide') {
            row.classList.add('hidden-in-quote');
        }

        row.innerHTML = `
            <td class="item-no text-center" style="vertical-align: middle;"></td>
            <td style="background-color: #f8f9fa;">
                <div class="d-flex align-items-center">
                    <span class="sub-item-number me-2 fw-bold"></span>
                    <input type="text" name="items[item_name][]" class="form-control form-control-sm item-name" required placeholder="Sub-item name" 
                        value="${subItemData.item_name || ''}">
                </div>
                <div class="ps-4">
                    <textarea name="items[description][]" class="form-control form-control-sm item-description mt-1" rows="1" placeholder="Description..." 
                            style="display: ${subItemData.description ? 'block' : 'none'};">${subItemData.description || ''}</textarea>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-description-btn" style="display: ${subItemData.description ? 'inline-block' : 'none'};">&times; Remove Description</button>
                    <button type="button" class="btn btn-outline-success btn-sm add-description-btn" style="display: ${subItemData.description ? 'none' : 'inline-block'};">+ Description</button>
                </div>
                <input type="hidden" name="items[temp_id][]" value="">
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
            <td><input type="text" class="form-control-plaintext form-control-sm text-end item-price" readonly></td>
            <td><input type="number" name="items[quantity][]" class="form-control form-control-sm item-quantity" min="1" value="${subItemData.quantity || '1'}" required></td>
            <td><input type="text" class="form-control-plaintext form-control-sm text-end item-total" readonly></td>
            <td class="text-center" style="vertical-align: middle;"><button type="button" class="btn btn-danger btn-sm remove-item-btn"><i class="fas fa-trash"></i></button></td>
        `;

        // แก้ปัญหาลำดับการเพิ่ม: ค้นหารายการย่อยตัวสุดท้ายแล้วค่อยเพิ่มแถวใหม่ต่อท้าย
        let lastSibling = parentRow;
        let nextElement = parentRow.nextElementSibling;
        while (nextElement && nextElement.classList.contains('sub-item-row')) {
            lastSibling = nextElement;
            nextElement = nextElement.nextElementSibling;
        }
        lastSibling.insertAdjacentElement('afterend', row);
        
        return row;
    }

    /**
     * อ่านข้อมูลเดิมจากหน้า Edit มาสร้างฟอร์ม
     */
    function populateFormWithData() {
    // ฟังก์ชันผู้ช่วย: แปลงข้อมูล flat array (จาก DB) ให้เป็น nested (มี children)
        function transformFlatItemsToNested(flatItems) {
            const itemMap = {};
            const nestedItems = [];

            if (!flatItems) return [];

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

        const dataElement = document.getElementById('quotation-data');
        if (!dataElement || !dataElement.textContent.trim()) {
            // ถ้าเป็นหน้าสร้างใหม่ หรือไม่มีข้อมูล ให้เพิ่มแถวเปล่า 1 แถวแล้วจบการทำงาน
            addNewItemRow();
            calculateTotals();
            updateItemNumbers();
            return;
        }

        // --- กรณีเป็นหน้า "แก้ไข" (เจอข้อมูล JSON) ---
        const rawData = JSON.parse(dataElement.textContent);
        const flatItems = rawData.items || [];
        const nestedItems = transformFlatItemsToNested(flatItems);

        itemsContainer.innerHTML = ''; // เคลียร์ตารางเก่าทิ้ง

        // วนลูปสร้างรายการหลักและรายการย่อยตามลำดับที่ถูกต้อง
        nestedItems.forEach(mainItem => {
            const mainRow = addNewItemRow(mainItem); // สร้างแถวแม่พร้อมข้อมูล
            mainItem.children.forEach(subItem => {
                addNewSubItemRow(mainRow, subItem); // สร้างแถวลูกพร้อมข้อมูล
            });
        });

        calculateTotals();
        updateItemNumbers();
    }

    // --- 3. Event Listeners และการเริ่มต้นทำงาน ---

    if (addItemBtn) {
        addItemBtn.addEventListener('click', () => {
             const newRow = addNewItemRow();
             updateItemNumbers();
             calculateTotals();
        });
    }

    if (itemsContainer) {
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
        
        // เปิดใช้งาน Drag and Drop
        new Sortable(itemsContainer, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: function () {
                updateItemNumbers();
            }
        });
    }
     // ✅ B. ถ้าเป็นหน้าเลือกโปรเจกต์ (มีปุ่ม Next)
    // ✅ B. ถ้าเป็นหน้าเลือกโปรเจกต์ (มีปุ่ม Next)
    if (nextStepBtn) {
        // 1. ประกาศตัวแปรให้ครบ รวมถึง container ที่ครอบ project select
        const customerSelect = document.getElementById('customer_id');
        const projectContainer = document.getElementById('project-container'); // <-- เพิ่มบรรทัดนี้
        const projectSelect = document.getElementById('project_id');

        if (!customerSelect || !projectContainer || !projectSelect) {
            console.error("Customer or Project dropdown not found!");
            return;
        }

        const allProjectOptions = Array.from(projectSelect.options);

        function filterProjectsAndToggleVisibility() {
            const customerId = customerSelect.value;

            // 2. เพิ่ม Logic การซ่อน/แสดง Container
            if (customerId) {
                // ถ้าเลือกลูกค้าแล้ว ให้แสดง container
                projectContainer.style.display = 'block';
            } else {
                // ถ้ายังไม่เลือก ให้ซ่อน container กลับไป
                projectContainer.style.display = 'none';
                projectSelect.value = ""; // Reset ค่าที่เลือกไว้
                return; // หยุดทำงาน ไม่ต้องกรอง
            }

            // 3. กรองรายการ Project (เหมือนเดิม)
            projectSelect.innerHTML = '';
            const defaultOption = document.createElement('option');
            defaultOption.value = "";
            defaultOption.textContent = '-- Select a project --';
            defaultOption.selected = true;
            projectSelect.appendChild(defaultOption);

            allProjectOptions.forEach(option => {
                if (option.dataset.customerId === customerId) {
                    projectSelect.appendChild(option.cloneNode(true));
                }
            });
            projectSelect.disabled = false;
        }

        // 4. ผูก Event Listener
        customerSelect.addEventListener('change', filterProjectsAndToggleVisibility);

        nextStepBtn.addEventListener('click', function () {
            const projectId = projectSelect.value;
            if (projectId) {
                // ควรจะเข้ารหัส ID ก่อนส่งไปใน URL เพื่อให้สอดคล้องกับ Controller ของคุณ
                window.location.href = '/mcvpro/public/quotations/create/' + projectId;
            } else {
                alert('Please select a customer and a project first.');
            }
        });
    }
    if (quotationForm) {
        quotationForm.addEventListener('submit', function() {
            // ค้นหา dropdown ลูกค้าในหน้านั้นๆ
            const customerSelectOnSubmit = document.getElementById('customer_id');

            // ตรวจสอบก่อนว่ามี dropdown นี้อยู่จริงหรือไม่
            if (customerSelectOnSubmit) {
                // ถ้ามี (เฉพาะในหน้า Create) ให้ปลดล็อกก่อน submit
                customerSelectOnSubmit.disabled = false;
            }
            
            // ถ้าไม่เจอ (ในหน้า Edit) โค้ดส่วนนี้จะไม่ทำงาน และฟอร์มจะ submit ได้ปกติ
        });
    }
    // --- 4. เริ่มต้นการทำงาน ---
    populateFormWithData();
});
    
    