#!/bin/bash
# app.js dosyasındaki loadCustomerDetails fonksiyonunu değiştir

# Önce backup al
cp /home/abdullahemirkirecci/Downloads/VS/app.js /home/abdullahemirkirecci/Downloads/VS/app.js.bak

# 544-575 satırları arası fonksiyonu değiştir
head -n 540 /home/abdullahemirkirecci/Downloads/VS/app.js > /home/abdullahemirkirecci/Downloads/VS/app_new.js

# Yeni fonksiyonu ekle
cat >> /home/abdullahemirkirecci/Downloads/VS/app_new.js << 'FUNC'

/**
 * Müşteri detaylarını göster
 */
async function loadCustomerDetails(cid) {
    const result = await apiCall(`/customer_detail.php?cid=${cid}`);
    
    if (!result.success) {
        showNotification('Müşteri detayları yüklenemedi: ' + result.error, 'error');
        return;
    }
    
    const { customer, vehicles, transactions } = result.data;
    
    // 1) Müşteri Profil Bilgisi Güncelle
    const avatar = document.getElementById('cust-avatar');
    const name = document.getElementById('cust-name');
    const info = document.getElementById('cust-info');
    
    if (avatar && name && info) {
        const initials = customer.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        avatar.textContent = initials;
        name.textContent = customer.name;
        info.textContent = \`CID: \${customer.cid} • Üyelik: \${customer.active_subscription}\`;
    }
    
    // 2) Araçlar Tablosu Güncelle
    const vehiclesTable = document.getElementById('cust-vehicles-table');
    if (vehiclesTable) {
        let html = '<div class="t-row t-head"><div>Plaka</div><div>Marka</div><div>Model</div><div>Renk</div></div>';
        
        if (vehicles.length === 0) {
            html += '<div class="t-row"><div colspan="4" style="text-align:center; padding:20px;">Bu müşteriye ait araç yok</div></div>';
        } else {
            vehicles.forEach(vehicle => {
                html += \`<div class="t-row"><div class="mono">\${vehicle.plate}</div><div>\${vehicle.make || '-'}</div><div>\${vehicle.model || '-'}</div><div>\${vehicle.color || '-'}</div></div>\`;
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
                html += \`<div class="t-row"><div class="mono">#\${tx.record_id}</div><div class="mono">\${tx.plate}</div><div>\${formatTime(tx.entry_time)}</div><div>\${exitTime}</div><div>\${fee}</div></div>\`;
            });
        }
        
        transactionsTable.innerHTML = html;
    }
}
FUNC

# Geri kalan kısmı ekle (576. satırdan sonrası)
tail -n +576 /home/abdullahemirkirecci/Downloads/VS/app.js >> /home/abdullahemirkirecci/Downloads/VS/app_new.js

# Eski dosyayı yenisiyle değiştir
mv /home/abdullahemirkirecci/Downloads/VS/app_new.js /home/abdullahemirkirecci/Downloads/VS/app.js

echo "✅ app.js güncellendi"
