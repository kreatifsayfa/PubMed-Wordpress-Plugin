# PubMed Health Importer - WordPress Eklentisi

Türkçe sağlık blogları için tam otomatik içerik oluşturan premium WordPress eklentisi. PubMed'den kadın ve bebek sağlığı ile ilgili makaleleri çeker, Gemini AI ile Türkçe blog yazılarına dönüştürür ve SEO optimizasyonu yapar.

## 🎯 Özellikler

### ✅ Tam İşlevsel - Placeholder Yok!
- **Otomatik Blog Yazısı Oluşturma**: PubMed makalelerini Gemini AI ile 1500+ kelimelik Türkçe blog yazılarına dönüştürür
- **Türkçe Çeviri**: İngilizce makaleleri otomatik olarak Türkçe'ye çevirir
- **SEO Optimizasyonu**: Google için tam optimizasyon (meta etiketler, schema markup, başlık yapısı)
- **İç Link Otomasyonu**: ⭐ İçeriklere otomatik olarak ilgili makalelere iç linkler ekler (SEO için kritik)
- **Akıllı FAQ Oluşturma**: ⭐ İçerikten otomatik soru-cevap çıkarır, H2/H3 başlıklarını analiz eder
- **Featured Snippet**: Google'da sıfır snippet (featured snippet) için optimize içerik
- **Placeholder Yok**: Her şey doğrudan oluşturulur, hiçbir şey "doldurulacak" olarak bırakılmaz

### 📊 İçerik İşleme
**Gemini AI Etkinse (Önerilen):**
- 1500+ kelime Türkçe blog yazısı
- Giriş, gelişme, sonuç bölümleri
- H2 ve H3 başlıkları
- İpucu kutuları
- 8-10 adet SSS
- Schema markup
- SEO optimizasyonu

**Model Seçimi:**

| Model | Hız | Kalite | Maliyet | Kullanım |
|-------|-----|--------|---------|----------|
| **Gemini 2.5 Flash** | ⚡ Hızlı | 🟡 İyi | 💰 Uygun | Blog yazıları için önerilen |
| **Gemini 2.5 Pro** | 🔄 Orta | ⭐ Premium | 💳 Yüksek | Profesyonel içerik için |

**Gemini AI Kapalıysa:**
- Temel içerik yapısı
- MeSH terimleri açıklamaları
- Makale özeti
- Bilimsel kaynak bilgileri
- Anahtar kelime bulutu

## 🚀 Kurulum

### 1. Gereksinimler
- WordPress 5.0+
- PHP 7.4+
- **Gemini AI API Anahtarı** (Zorunlu - blog yazısı oluşturmak için)
- PubMed API Anahtarı (Opsiyonel - daha yüksek istek limiti için)

### 2. API Anahtarı Alma

#### Gemini AI API (Zorunlu)
1. [Google AI Studio](https://makersuite.google.com/app/apikey) adresine gidin
2. "Create API Key" butonuna tıklayın
3. API anahtarınızı kopyalayın
4. WordPress admin panelinde > PubMed Health > Ayarlar > Gemini AI API Anahtarı alanına yapıştırın

#### PubMed API (Opsiyonel)
1. [NCBI Account](https://www.ncbi.nlm.nih.gov/account/) oluşturun
2. Account Settings > API Key Management kısmından yeni bir API anahtarı oluşturun
3. WordPress admin panelinde > PubMed Health > Ayarlar > PubMed API Anahtarı alanına yapıştırın

### 3. Eklenti Ayarları

WordPress admin panelinde **PubMed Health > Ayarlar** sayfasına gidin:

#### Genel Ayarlar
- **Varsayılan Yazar**: İçe aktarılan makalelerin yazarı
- **Önbellek Süresi**: API yanıtlarının saklanma süresi (varsayılan: 24 saat)

#### API Ayarlar
- **Gemini AI API Anahtarı**: ⭐ Zorunlu - blog yazısı oluşturmak için
- **Gemini AI Modeli**:
  - **Gemini 2.5 Flash** (Önerilen): ⚡ Hızlı ve uygun fiyatlı
  - **Gemini 2.5 Pro**: 💎 Premium kalite, daha detaylı içerik
- **PubMed API Anahtarı**: İsteğe bağlı
- **Email**: PubMed API için (istek limiti artırır)

#### İçerik Ayarları
- **İçerik Zenginleştirme**: ⭐ "Evet" olarak işaretleyin (Gemini AI ile blog yazısı oluşturur)
- **Otomatik Yayınlama**: "Evet" seçerseniz makaleler otomatik yayınlanır
- **MeSH Terimleri**: Aramalarda kullanılacak tıbbi terimler

#### SEO Ayarları
- **SEO Optimizasyonu**: "Evet" - meta etiketler ve schema markup ekler
- **Featured Snippet**: "Evet" - Google snippet'leri için optimize eder
- **FAQ Oluşturma**: "Evet" - otomatik SSS bölümü ekler

## 📖 Kullanım

### 1. Arama Yapma
1. **PubMed Health > Arama** sayfasına gidin
2. Arama kutusuna anahtar kelime girin (örn: "pregnancy nutrition")
3. Sonuç sayısını seçin (varsayılan: 10)
4. "Ara" butonuna tıklayın
5. Sonuçlar listelenecektir

### 2. Makale İçe Aktarma
1. Arama sonuçlarından bir makale seçin
2. "İçe Aktar" butonuna tıklayın
3. Sistem şunları otomatik yapar:
   - PubMed'den makale detaylarını çeker
   - Gemini AI ile Türkçe blog yazısı oluşturur (1500+ kelime)
   - SEO meta etiketlerini ekler
   - FAQ bölümünü oluşturur
   - Schema markup ekler
4. İşlem tamamlandığında "Düzenle" veya "Görüntüle" linklerine tıklayabilirsiniz

### 3. Zamanlanmış Aramalar
1. **PubMed Health > Zamanlanmış Aramalar** sayfasına gidin
2. "Yeni Zamanlanmış Arama" formunu doldurun:
   - **Ad**: Arama için bir isim
   - **Açıklama**: İsteğe bağlı açıklama
   - **Sorgu**: Arama terimi (örn: "postpartum depression")
   - **Sonuç Sayısı**: Her aramada çekilecek makale sayısı
   - **Zamanlama**: Saatlik, Günlük, veya Haftalık
3. "Kaydet" butonuna tıklayın

Zamanlanmış aramalar otomatik olarak çalışır ve yeni makaleleri içe aktarır.

## 🔧 Ayarlar Kontrol Listesi

İçe aktarma öncesi bu ayarların doğru yapılandığından emin olun:

| Ayar | Değer | Zorunlu |
|------|-------|---------|
| Gemini AI API Key | XXXXXXXX | ⭐ Evet |
| İçerik Zenginleştirme | Evet | ⭐ Evet |
| SEO Optimizasyonu | Evet | Önerilen |
| FAQ Oluşturma | Evet | Önerilen |
| Otomatik Yayınlama | Hayır/Evet | İsteğe bağlı |

## 📝 Oluşturulan İçerik Yapısı

Her içe aktarılan makale şu bölümleri içerir:

```html
<!-- Giriş Bölümü -->
- Yazarlar
- Dergi
- Yayın Tarihi

<!-- MeŞ Terimleri -->
- Her terim için açıklama
- Görsel olarak vurgulanmış

<!-- Özet -->
- Orijinal makale özeti (Türkçe'ye çevrilmiş)

<!-- Detaylı İnceleme (Gemini AI) -->
- 1500+ kelime Türkçe içerik
- H2 ve H3 başlıkları
- Liste ve tablolar
- Pratik öneriler
- İpucu kutuları

<!-- Sıkça Sorulan Sorular -->
- 8-10 adet SSS
- Accordion tarzı gösterim

<!-- Kaynak -->
- Bilimsel kaynak bilgileri
- PubMed linki
- Tıbbi uyarı
```

## 🐛 Sorun Giderme

### "İçerik zenginleştirme özelliği etkin değil" hatası
- **Çözüm**: Ayarlar sayfasından "İçerik Zenginleştirme" seçeneğini "Evet" yapın ve Gemini AI API anahtarınızı girin.

### İçerik İngilizce geliyor
- **Çözüm**: Gemini AI API anahtarınızın doğru olduğundan emin olun. Türkçe çeviri otomatik yapılır.

### Blog yazısı çok kısa
- **Çözüm**: Bu normalde olmamalı. Gemini AI minimum 1500 kelime üretir. Kısa içerik geliyorsa API limitlerinizi kontrol edin.

### PubMed arama boş sonuç döndürüyor
- **Çözüm**: MeSH terimlerinizi kontrol edin. Varsayılan terimler kadın ve bebek sağlığı ile ilgili makaleleri arar.

## 📄 Lisans

Bu eklenti regl.net.tr tarafından geliştirilmiştir.

## 🆘 Destek

Sorun yaşarsanız:
1. WordPress hata ayıklama modunu açın (`WP_DEBUG` true)
2. Browser konsolunu kontrol edin
3. API anahtarlarınızın doğru olduğunu doğrulayın

---

## 💡 İpuçları

- **İlk deneme**: Önce tek bir makaleyi içe aktararak sonucu kontrol edin
- **SEO için**: "Otomatik Yayınlama" seçeneğini "Hayır" yapın, içerikleri inceleyip düzenleyerek yayınlayın
- **Zamanlama**: Günlük aramalar sitenizi sürekli güncel tutar
- **Kategoriler**: MeSH terimleri otomatik kategorilere dönüştürülür

---

**Not**: Bu eklenti bilgilendirme amaçlıdır. İçerikler profesyonel tıbbi tavsiye yerine geçmez.
