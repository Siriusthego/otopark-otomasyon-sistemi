/**
 * Otopark Yönetim Sistemi - Frontend JavaScript
 * API entegrasyonu ve dinamik veri yükleme
 */

// API Base URL (production'da değiştirin)
const API_BASE = '/api';

// =============================================
// HELPER FUNCTIONS
// =============================================

/**
 * API çağrısı yapar
 */
async function apiCall(endpoint, method = 'GET', body = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        }
    };

    if (body && method !== 'GET') {
        options.body = JSON.stringify(body);
    }

    try {
        const response = await fetch(API_BASE + endpoint, options);
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, error: error.message };
    }
}

/**
 * TL formatla
 */
function formatTRY(amount) {
    return '₺ ' + parseFloat(amount).toLocaleString('tr-TR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

/**
 * Tarih/saat formatla
 */
function formatDateTime(datetime) {
    const d = new Date(datetime);
    return d.toLocaleString('tr-TR', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Sadece saat formatla
 */
function formatTime(datetime) {
    const d = new Date(datetime);
    return d.toLocaleTimeString('tr-TR', {
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Toast notification (basit alert yerine)
 */
function showNotification(message, type = 'info') {
    // Basit alert kullanıyoruz, daha sonra güzelleştirilebilir
    if (type === 'error') {
        alert('❌ ' + message);
    } else if (type === 'success') {
        alert('✅ ' + message);
    } else {
        alert('ℹ️ ' + message);
    }
}

// =============================================
// DASHBOARD
// =============================================

/**
 * Dashboard verilerini yükle
 */
async function loadDashboard() {
    const result = await apiCall('/dashboard.php');

    if (!result.success) {
        showNotification('Dashboard yüklenemedi: ' + result.error, 'error');
        return;
    }

    const data = result.data;

    // Doluluk
    document.getElementById('kpi-occupancy').textContent = data.occupancy.occupancy_rate + '%';
    document.getElementById('kpi-occupancy-sub').textContent =
        `Toplam ${data.occupancy.total_spaces} alan • ${data.occupancy.occupied} dolu`;
    document.getElementById('kpi-occupancy-bar').style.width = data.occupancy.occupancy_rate + '%';

    // Gelir
    document.getElementById('kpi-revenue').textContent = formatTRY(data.revenue.today_revenue);

    // İşlemler
    document.getElementById('kpi-tx').textContent = data.transactions.total_today;
    document.getElementById('kpi-in').textContent = data.transactions.entries;
    document.getElementById('kpi-out').textContent = data.transactions.exits;

    // Abonelikler
    document.getElementById('kpi-subs').textContent = data.subscriptions.active_count;

    // Sidebar mini card güncelle
    const miniCard = document.querySelector('.mini-card');
    if (miniCard) {
        miniCard.innerHTML = `
            <div class="mini-title">Anlık Durum</div>
            <div class="mini-row">
                <span class="badge ok">Boş</span>
                <span>${data.occupancy.available}</span>
            </div>
            <div class="mini-row">
                <span class="badge warn">Dolu</span>
                <span>${data.occupancy.occupied}</span>
            </div>
            <div class="mini-row">
                <span class="badge muted">Bakım</span>
                <span>${data.occupancy.maintenance}</span>
            </div>
        `;
    }

    // İçerideki araçlar tablosu
    renderInsideCars(data.inside_cars);
}

/**
 * İçerideki araçları tabloya yaz
 */
function renderInsideCars(cars) {
    const container = document.querySelector('#dashboard .table');
    if (!container) return;

    let html = `
        <div class="t-row t-head">
            <div>Plaka</div><div>Park</div><div>Kat</div><div>Yer</div><div>Giriş</div><div>Durum</div>
        </div>
    `;

    if (cars.length === 0) {
        html += `
            <div class="t-row">
                <div colspan="6" style="text-align:center; color: var(--muted);">
                    Şu anda içeride araç yok
                </div>
            </div>
        `;
    } else {
        cars.forEach(car => {
            html += `
                <div class="t-row">
                    <div class="mono">${car.plate}</div>
                    <div>${car.lot_name}</div>
                    <div>${car.floor_name}</div>
                    <div>${car.space_code}</div>
                    <div>${formatTime(car.entry_time)}</div>
                    <div><span class="badge warn">Dolu</span></div>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

// =============================================
// ARAÇ GİRİŞİ
// =============================================

/**
 * Giriş formunu hazırla
 */
async function setupEntryForm() {
    // Boş park yerlerini yükle
    const spacesResult = await apiCall('/spaces.php?status=Bos');
    const tariffsResult = await apiCall('/tariffs.php');

    if (!spacesResult.success || !tariffsResult.success) {
        showNotification('Form verileri yüklenemedi', 'error');
        return;
    }

    // Park yerleri dropdown'ını doldur
    const spaceSelect = document.getElementById('entry-space');
    if (spaceSelect) {
        spaceSelect.innerHTML = '<option value="">-- Boş park yeri seçin --</option>';
        spacesResult.data.spaces.forEach(space => {
            spaceSelect.innerHTML += `
                <option value="${space.space_id}">
                    ${space.space_code} - ${space.floor_name} (${space.space_type})
                </option>
            `;
        });
    }

    // Tarife dropdown'ını doldur
    const tariffSelect = document.getElementById('entry-tariff');
    if (tariffSelect) {
        tariffSelect.innerHTML = '<option value="">-- Tarife seçin --</option>';
        tariffsResult.data.forEach(tariff => {
            tariffSelect.innerHTML += `
                <option value="${tariff.tid}">
                    ${tariff.name} (${formatTRY(tariff.hourly_rate)}/saat)
                </option>
            `;
        });
    }
}

/**
 * Araç girişi yap
 */
async function submitEntry() {
    const plate = document.getElementById('entry-plate').value.trim();
    const space_id = document.getElementById('entry-space').value;
    const tid = document.getElementById('entry-tariff').value;

    if (!plate || !space_id || !tid) {
        showNotification('Lütfen tüm alanları doldurun', 'error');
        return;
    }

    const result = await apiCall('/entry.php', 'POST', { plate, space_id, tid });

    if (result.success) {
        showNotification('Araç girişi başarılı!', 'success');
        // Formu temizle
        document.getElementById('entry-plate').value = '';
        // Dashboard'u güncelle
        loadDashboard();
        // Park yerlerini yeniden yükle
        setupEntryForm();
    } else {
        showNotification(result.error, 'error');
    }
}

// =============================================
// ARAÇ ÇIKIŞI
// =============================================

/**
 * İçerideki araçları çıkış için listele
 */
async function loadExitRecords() {
    const dashResult = await apiCall('/dashboard.php');

    if (!dashResult.success) {
        showNotification('Çıkış kayıtları yüklenemedi', 'error');
        return;
    }

    const cars = dashResult.data.inside_cars;
    const container = document.querySelector('#exit .table');

    if (!container) return;

    let html = `
        <div class="t-row t-head">
            <div>Record</div><div>Plaka</div><div>Giriş</div><div>Yer</div><div>Tarife</div><div></div>
        </div>
    `;

    if (cars.length === 0) {
        html += `
            <div class="t-row">
                <div colspan="6" style="text-align:center; color: var(--muted);">
                    Çıkış yapacak araç yok
                </div>
            </div>
        `;
    } else {
        cars.forEach(car => {
            html += `
                <div class="t-row">
                    <div class="mono">#${car.record_id}</div>
                    <div class="mono">${car.plate}</div>
                    <div>${formatTime(car.entry_time)}</div>
                    <div>${car.space_code}</div>
                    <div>${car.tariff_name}</div>
                    <div>
                        <button class="btn btn-soft btn-xs" onclick="processExit(${car.record_id})">
                            Seç
                        </button>
                    </div>
                </div>
            `;
        });
    }

    container.innerHTML = html;
}

/**
 * Çıkış işlemi yap
 */
async function processExit(record_id) {
    if (!confirm('Çıkış işlemini onaylıyor musunuz?')) {
        return;
    }

    const result = await apiCall('/exit.php', 'POST', { record_id });

    if (result.success) {
        const data = result.data;

        // Ücret bilgisini göster
        const feeContainer = document.querySelector('#exit .kpi-block');
        if (feeContainer) {
            feeContainer.innerHTML = `
                <div class="kpi-line">
                    <span class="muted">Süre</span>
                    <span class="strong">${Math.floor(data.duration_min / 60)} saat ${data.duration_min % 60} dk</span>
                </div>
                <div class="kpi-line">
                    <span class="muted">Tarife</span>
                    <span class="strong">${data.tariff_name}</span>
                </div>
                <div class="kpi-line">
                    <span class="muted">Tutar</span>
                    <span class="strong big">${formatTRY(data.fee)}</span>
                </div>
            `;
        }

        // Ödeme butonunu aktif et
        const payBtn = document.querySelector('#exit-pay-btn');
        if (payBtn) {
            payBtn.disabled = false;
            payBtn.setAttribute('data-record-id', record_id);
        }

        showNotification(`Çıkış tamamlandı. Ücret: ${formatTRY(data.fee)}`, 'success');
        loadExitRecords();
    } else {
        showNotification(result.error, 'error');
    }
}

/**
 * Ödeme al
 */
async function submitPayment() {
    const payBtn = document.querySelector('#exit-pay-btn');
    const record_id = payBtn.getAttribute('data-record-id');

    if (!record_id) {
        showNotification('Önce bir araç çıkışı yapın', 'error');
        return;
    }

    // Ödeme yöntemi seç (segmented control'den)
    const methodBtns = document.querySelectorAll('#exit .seg-btn');
    let method = 'Nakit';
    methodBtns.forEach(btn => {
        if (btn.classList.contains('active')) {
            method = btn.textContent.trim();
        }
    });

    const result = await apiCall('/payments.php', 'POST', { record_id, method });

    if (result.success) {
        showNotification('Ödeme başarıyla kaydedildi!', 'success');
        // Dashboard'u güncelle
        loadDashboard();
        // Formu sıfırla
        loadExitRecords();
        payBtn.disabled = true;
        payBtn.removeAttribute('data-record-id');
    } else {
        showNotification(result.error, 'error');
    }
}

// =============================================
// PARK YERLERİ
// =============================================

/**
 * Park yerlerini listele
 */
async function loadSpaces() {
    const result = await apiCall('/spaces.php');

    if (!result.success) {
        showNotification('Park yerleri yüklenemedi', 'error');
        return;
    }

    const spaces = result.data.spaces;
    const container = document.querySelector('#spaces .space-grid');

    if (!container) return;

    let html = '';
    spaces.forEach(space => {
        let statusClass = 'ok';
        if (space.status === 'Dolu') statusClass = 'warn';
        if (space.status === 'Bakim') statusClass = 'muted';

        html += `
            <div class="space ${statusClass}">
                <div class="space-code">${space.space_code}</div>
                <div class="space-meta">${space.space_type}</div>
                <div class="badge ${statusClass}">${space.status}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// =============================================
// NAVIGATION
// =============================================

/**
 * Sayfa geçişlerini yönet
 */
function setupNavigation() {
    const navItems = document.querySelectorAll('.nav-item');
    const sections = document.querySelectorAll('.section');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();

            // Active class'ları güncelle
            navItems.forEach(n => n.classList.remove('active'));
            item.classList.add('active');

            // Bölümleri gizle/göster
            const targetId = item.getAttribute('href').substring(1);
            sections.forEach(section => {
                if (section.id === targetId) {
                    section.style.display = 'block';

                    // Sayfa yüklendiğinde veri yükle
                    if (targetId === 'dashboard') loadDashboard();
                    if (targetId === 'entry') setupEntryForm();
                    if (targetId === 'exit') loadExitRecords();
                    if (targetId === 'spaces') loadSpaces();
                    if (targetId === 'customers') loadCustomers();
                } else {
                    section.style.display = 'none';
                }
            });
        });
    });

    // İlk yükleme: dashboard'u göster
    loadDashboard();
}

// =============================================
// MÜŞTERİ YÖNETİMİ
// =============================================

/**
 * Müşteri listesini yükle
 */
async function loadCustomers() {
    const result = await apiCall('/customers.php');

    if (!result.success) {
        showNotification('Müşteri listesi yüklenemedi', 'error');
        return;
    }

    const customers = result.data;
    const container = document.querySelector('#customers .table');

    if (!container) return;

    let html = `
        <div class="t-row t-head">
            <div>Ad Soyad</div><div>Telefon</div><div>E-posta</div><div></div>
        </div>
    `;

    customers.forEach(customer => {
        html += `
            <div class="t-row">
                <div>${customer.name}</div>
                <div class="mono">${customer.phone || '-'}</div>
                <div>${customer.email || '-'}</div>
                <div><button class="btn btn-soft btn-xs customer-detail-btn" data-cid="${customer.cid}">Aç</button></div>
            </div>
        `;
    });

    container.innerHTML = html;

    // Event delegation
    container.querySelectorAll('.customer-detail-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const cid = parseInt(btn.getAttribute('data-cid'));
            loadCustomerDetails(cid);
        });
    });
}

/**
 * Yeni müşteri ekle (prompt ile)
 */
async function addNewCustomer() {
    const name = prompt('Müşteri Adı:');
    if (!name || name.trim() === '') {
        showNotification('Müşteri adı gerekli', 'error');
        return;
    }

    const phone = prompt('Telefon (opsiyonel):');
    const email = prompt('E-posta (opsiyonel):');

    const result = await apiCall('/customers.php', 'POST', {
        name: name.trim(),
        phone: phone ? phone.trim() : '',
        email: email ? email.trim() : ''
    });

    if (result.success) {
        showNotification('Müşteri başarıyla eklendi!', 'success');
        loadCustomers();
    } else {
        showNotification(result.error, 'error');
    }
}

/**
 * Müşteri detaylarını göster
 */
async function loadCustomerDetails(cid) {
    const result = await apiCall(`/customer_detail.php?cid=${cid}`);

    if (!result.success) {
        showNotification('Müşteri detayları yüklenemedi: ' + result.error, 'error');
        return;
    }

    const data = result.data;
    const customer = data.customer;
    const vehicles = data.vehicles;
    const transactions = data.transactions;

    // 1) Müşteri Profil Bilgisi Güncelle
    const avatar = document.getElementById('cust-avatar');
    const name = document.getElementById('cust-name');
    const info = document.getElementById('cust-info');

    if (avatar && name && info) {
        const initials = customer.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        avatar.textContent = initials;
        name.textContent = customer.name;
        info.textContent = 'CID: ' + customer.cid + ' • Üyelik: ' + customer.active_subscription;
    }

    // 2) Araçlar Tablosu Güncelle
    const vehiclesTable = document.getElementById('cust-vehicles-table');
    if (vehiclesTable) {
        let html = '<div class="t-row t-head"><div>Plaka</div><div>Marka</div><div>Model</div><div>Renk</div></div>';

        if (vehicles.length === 0) {
            html += '<div class="t-row"><div colspan="4" style="text-align:center; padding:20px;">Bu müşteriye ait araç yok</div></div>';
        } else {
            vehicles.forEach(vehicle => {
                html += '<div class="t-row"><div class="mono">' + vehicle.plate + '</div><div>' + (vehicle.make || '-') + '</div><div>' + (vehicle.model || '-') + '</div><div>' + (vehicle.color || '-') + '</div></div>';
            });
        }

        vehiclesTable.innerHTML = html;
    }

    // 3) Son İşlemler Tablosu Güncelle
    const transactionsTable = document.getElementById('cust-transactions-table');
    if (transactionsTable) {
        let html = '<div class="t-row t-head"><div>Kayıt</div><div>Plaka</div><div>Giriş</div><div>Çıkış</div><div>Tutar</div></div>';

        if (transactions.length === 0) {
            html += '<div class="t-row"><div colspan="5" style="text-align:center; padding:20px;">Bu müşterinin işlem geçmişi yok</div></div>';
        } else {
            transactions.forEach(tx => {
                const exitTime = tx.exit_time ? formatTime(tx.exit_time) : '-';
                const fee = tx.fee ? formatTRY(tx.fee) : '-';
                html += '<div class="t-row"><div class="mono">#' + tx.record_id + '</div><div class="mono">' + tx.plate + '</div><div>' + formatTime(tx.entry_time) + '</div><div>' + exitTime + '</div><div>' + fee + '</div></div>';
            });
        }

        transactionsTable.innerHTML = html;
    }
}

// =============================================
// SEGMENTED CONTROL
// =============================================

/**
 * Segmented control button'larını aktif et
 */
function setupSegmentedControls() {
    const segButtons = document.querySelectorAll('.seg-btn');

    segButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            // Aynı parent içindeki diğer butonları pasif yap
            const parent = btn.parentElement;
            parent.querySelectorAll('.seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
}

// =============================================
// INIT
// =============================================

/**
 * Sayfa yüklendiğinde çalışacak
 */
document.addEventListener('DOMContentLoaded', () => {
    console.log('🚗 Otopark Yönetim Sistemi yüklendi');

    // Navigation kurulumu
    setupNavigation();

    // Segmented controls
    setupSegmentedControls();

    // Entry form button
    const entryBtn = document.querySelector('#entry .btn-primary');
    if (entryBtn) {
        entryBtn.addEventListener('click', submitEntry);
    }

    // Exit payment button
    const payBtn = document.querySelector('#exit .btn-primary');
    if (payBtn) {
        payBtn.id = 'exit-pay-btn';
        payBtn.addEventListener('click', submitPayment);
    }

    // Preload data for all pages (eager loading for better UX)
    setupEntryForm();
    loadExitRecords();
    loadSpaces();
    loadCustomers();

    // Customer management button
    const addCustomerBtn = document.querySelector('#customers .btn-primary');
    if (addCustomerBtn) {
        addCustomerBtn.addEventListener('click', addNewCustomer);
    }

    console.log('✅ Sistem hazır!');
});
