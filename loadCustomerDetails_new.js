/**
 * Müşteri detaylarını göster
 */
async function loadCustomerDetails(cid) {
    // Müşteri bilgisini al
    const customersResult = await apiCall('/customers.php');
    if (!customersResult.success) {
        showNotification('Müşteri detayları yüklenemedi', 'error');
        return;
    }

    const customer = customersResult.data.find(c => c.cid == cid);
    if (!customer) {
        showNotification('Müşteri bulunamadı', 'error');
        return;
    }

    // Müşteri araçlarını al
    const vehiclesResult = await apiCall(`/vehicles.php?cid=${cid}`);
    const vehicles = vehiclesResult.success ? vehiclesResult.data : [];

    // Müşteri profil bilgisini güncelle
    const profileContainer = document.querySelector('#customers .profile');
    if (profileContainer) {
        const initials = customer.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        profileContainer.innerHTML = `
            <div class="avatar big">${initials}</div>
            <div>
                <div class="strong">${customer.name}</div>
                <div class="muted small">CID: ${customer.cid} • Araç: ${customer.vehicle_count}</div>
            </div>
        `;
    }

    // Araçlar tablosunu güncelle - İKİNCİ .table elementi (ilk araçlar, sonra işlemler)
    const allTables = document.querySelectorAll('#customers .card:last-child .table');
    const vehiclesContainer = allTables[0]; // İlk tablo araçlar için

    if (vehiclesContainer) {
        let html = `
            <div class="t-row t-head">
                <div>Plaka</div><div>Marka</div><div>Model</div><div>Renk</div>
            </div>
        `;

        if (vehicles.length === 0) {
            html += `
                <div class="t-row">
                    <div colspan="4" style="text-align:center; color: var(--muted);">Kayıtlı araç yok</div>
                </div>
            `;
        } else {
            vehicles.forEach(vehicle => {
                html += `
                    <div class="t-row">
                        <div class="mono">${vehicle.plate}</div>
                        <div>${vehicle.make || '-'}</div>
                        <div>${vehicle.model || '-'}</div>
                        <div>${vehicle.color || '-'}</div>
                    </div>
                `;
            });
        }

        vehiclesContainer.innerHTML = html;
    }

    // Son işlemler tablosunu güncelle
    const dashResult = await apiCall('/dashboard.php');
    const transactionsContainer = allTables[1]; // İkinci tablo işlemler için

    if (transactionsContainer && dashResult.success) {
        const allCars = dashResult.data.inside_cars || [];
        const customerPlates = vehicles.map(v => v.plate);

        let html = `
            <div class="t-row t-head">
                <div>Plaka</div><div>Durum</div><div>Park</div><div>Giriş</div>
            </div>
        `;

        const activeCars = allCars.filter(car => customerPlates.includes(car.plate));

        if (activeCars.length === 0) {
            html += `
                <div class="t-row">
                    <div colspan="4" style="text-align:center; color: var(--muted);">Aktif işlem yok</div>
                </div>
            `;
        } else {
            activeCars.forEach(car => {
                html += `
                    <div class="t-row">
                        <div class="mono">${car.plate}</div>
                        <div><span class="badge warn">İçeride</span></div>
                        <div>${car.space_code}</div>
                        <div>${formatTime(car.entry_time)}</div>
                    </div>
                `;
            });
        }

        transactionsContainer.innerHTML = html;
    }
}
