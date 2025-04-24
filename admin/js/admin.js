/**
 * PubMed Health Importer Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    // ===============================
    // PubMed Arama Sayfası
    // ===============================
    
    // Arama formu gönderimi
    $('#pubmed-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var query = $('#pubmed-search-query').val();
        var count = $('#pubmed-search-count').val();
        
        if (!query) {
            return;
        }
        
        // Arama sonuçlarını temizle
        $('#pubmed-search-results').hide();
        $('#pubmed-results-list').empty();
        $('#pubmed-search-error').hide();
        
        // Spinner göster
        var $button = $('#pubmed-search-button');
        var $spinner = $button.next('.spinner');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_search',
                nonce: pubmed_health_importer.nonce,
                query: query,
                count: count
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    var results = response.data.results;
                    
                    // Sonuç sayısını göster
                    $('#pubmed-results-count').html('<p>' + results.total + ' sonuç bulundu.</p>');
                    
                    // Sonuçları listele
                    if (results.articles && results.articles.length > 0) {
                        $.each(results.articles, function(index, article) {
                            var template = $('#pubmed-result-template').html();
                            
                            // Template değişkenlerini değiştir
                            template = template.replace('{{ id }}', article.id);
                            template = template.replace('{{ title }}', article.title);
                            template = template.replace('{{ authors }}', article.authors.join(', '));
                            template = template.replace('{{ journal }}', article.journal);
                            template = template.replace('{{ publication_date }}', article.publication_date);
                            template = template.replace('{{ abstract }}', article.abstract || 'Özet bulunamadı.');
                            
                            $('#pubmed-results-list').append(template);
                        });
                        
                        $('#pubmed-search-results').show();
                    } else {
                        $('#pubmed-results-count').html('<p>Sonuç bulunamadı.</p>');
                        $('#pubmed-search-results').show();
                    }
                } else {
                    $('#pubmed-search-error').find('p').text(response.data.message);
                    $('#pubmed-search-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-search-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-search-error').show();
            }
        });
    });
    
    // Özeti görüntüle/gizle
    $(document).on('click', '.pubmed-view-abstract-button', function() {
        var $abstract = $(this).closest('.pubmed-result-item').find('.pubmed-result-abstract');
        
        if ($abstract.is(':visible')) {
            $abstract.slideUp();
            $(this).text(pubmed_health_importer.view_abstract);
        } else {
            $abstract.slideDown();
            $(this).text(pubmed_health_importer.hide_abstract);
        }
    });
    
    // İçe aktarma butonu
    $(document).on('click', '.pubmed-import-button', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $item = $button.closest('.pubmed-result-item');
        var pubmed_id = $item.data('pubmed-id');
        
        // Bildirimleri temizle
        $('#pubmed-import-success, #pubmed-import-error').hide();
        
        // Spinner göster
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_import',
                nonce: pubmed_health_importer.nonce,
                pubmed_id: pubmed_id
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Başarı mesajı göster
                    var message = response.data.message;
                    
                    if (response.data.edit_url && response.data.view_url) {
                        message += ' <a href="' + response.data.edit_url + '" target="_blank">Düzenle</a> | <a href="' + response.data.view_url + '" target="_blank">Görüntüle</a>';
                    }
                    
                    $('#pubmed-import-success').find('p').html(message);
                    $('#pubmed-import-success').show();
                    
                    // Butonu devre dışı bırak
                    $button.prop('disabled', true).text('İçe Aktarıldı');
                } else {
                    $('#pubmed-import-error').find('p').text(response.data.message);
                    $('#pubmed-import-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-import-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-import-error').show();
            }
        });
    });
    
    // ===============================
    // Makaleler Sayfası
    // ===============================
    
    // İçerik zenginleştirme butonu
    $('.pubmed-enhance-button').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var post_id = $button.data('post-id');
        
        // Bildirimleri temizle
        $('#pubmed-enhance-success, #pubmed-enhance-error').hide();
        
        // Spinner göster
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_enhance_content',
                nonce: pubmed_health_importer.nonce,
                post_id: post_id
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Başarı mesajı göster
                    var message = response.data.message;
                    
                    if (response.data.edit_url && response.data.view_url) {
                        message += ' <a href="' + response.data.edit_url + '" target="_blank">Düzenle</a> | <a href="' + response.data.view_url + '" target="_blank">Görüntüle</a>';
                    }
                    
                    $('#pubmed-enhance-success').find('p').html(message);
                    $('#pubmed-enhance-success').show();
                } else {
                    $('#pubmed-enhance-error').find('p').text(response.data.message);
                    $('#pubmed-enhance-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-enhance-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-enhance-error').show();
            }
        });
    });
    
    // ===============================
    // Zamanlanmış Aramalar Sayfası
    // ===============================
    
    // Zamanlanmış arama formu gönderimi
    $('#pubmed-scheduled-search-form').on('submit', function(e) {
        e.preventDefault();
        
        var id = $('#pubmed-scheduled-search-id').val();
        var name = $('#pubmed-scheduled-search-name').val();
        var description = $('#pubmed-scheduled-search-description').val();
        var query = $('#pubmed-scheduled-search-query').val();
        var count = $('#pubmed-scheduled-search-count').val();
        var schedule = $('#pubmed-scheduled-search-schedule').val();
        
        if (!name || !query) {
            return;
        }
        
        // Bildirimleri temizle
        $('#pubmed-scheduled-search-success, #pubmed-scheduled-search-error').hide();
        
        // Spinner göster
        var $button = $('#pubmed-scheduled-search-save-button');
        var $spinner = $button.next('.spinner');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_save_scheduled_search',
                nonce: pubmed_health_importer.nonce,
                id: id,
                name: name,
                description: description,
                query: query,
                count: count,
                schedule: schedule
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Başarı mesajı göster
                    $('#pubmed-scheduled-search-success').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-success').show();
                    
                    // Formu sıfırla
                    $('#pubmed-scheduled-search-id').val('0');
                    $('#pubmed-scheduled-search-name').val('');
                    $('#pubmed-scheduled-search-description').val('');
                    $('#pubmed-scheduled-search-query').val('');
                    $('#pubmed-scheduled-search-count').val('10');
                    $('#pubmed-scheduled-search-schedule').val('daily');
                    
                    // İptal butonunu gizle
                    $('#pubmed-scheduled-search-cancel-button').hide();
                    
                    // Sayfayı yenile
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#pubmed-scheduled-search-error').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-scheduled-search-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-scheduled-search-error').show();
            }
        });
    });
    
    // Zamanlanmış arama düzenleme butonu
    $('.pubmed-scheduled-search-edit-button').on('click', function() {
        var $row = $(this).closest('tr');
        var id = $row.data('id');
        var name = $row.find('.name strong').text();
        var description = $row.find('.name .description').text();
        var query = $row.find('.query').text();
        var schedule = $row.find('.schedule').text();
        
        // Formu doldur
        $('#pubmed-scheduled-search-id').val(id);
        $('#pubmed-scheduled-search-name').val(name);
        $('#pubmed-scheduled-search-description').val(description);
        $('#pubmed-scheduled-search-query').val(query);
        
        // Zamanlamayı seç
        if (schedule.indexOf('Saatlik') !== -1) {
            $('#pubmed-scheduled-search-schedule').val('hourly');
        } else if (schedule.indexOf('Günlük') !== -1) {
            $('#pubmed-scheduled-search-schedule').val('daily');
        } else if (schedule.indexOf('Haftalık') !== -1) {
            $('#pubmed-scheduled-search-schedule').val('weekly');
        }
        
        // İptal butonunu göster
        $('#pubmed-scheduled-search-cancel-button').show();
        
        // Forma kaydır
        $('html, body').animate({
            scrollTop: $('#pubmed-scheduled-search-form').offset().top - 50
        }, 500);
    });
    
    // Zamanlanmış arama iptal butonu
    $('#pubmed-scheduled-search-cancel-button').on('click', function() {
        // Formu sıfırla
        $('#pubmed-scheduled-search-id').val('0');
        $('#pubmed-scheduled-search-name').val('');
        $('#pubmed-scheduled-search-description').val('');
        $('#pubmed-scheduled-search-query').val('');
        $('#pubmed-scheduled-search-count').val('10');
        $('#pubmed-scheduled-search-schedule').val('daily');
        
        // İptal butonunu gizle
        $(this).hide();
    });
    
    // Zamanlanmış arama silme butonu
    $('.pubmed-scheduled-search-delete-button').on('click', function() {
        if (!confirm(pubmed_health_importer.confirm_delete)) {
            return;
        }
        
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $row = $button.closest('tr');
        var id = $row.data('id');
        
        // Bildirimleri temizle
        $('#pubmed-scheduled-search-success, #pubmed-scheduled-search-error').hide();
        
        // Spinner göster
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_delete_scheduled_search',
                nonce: pubmed_health_importer.nonce,
                id: id
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Başarı mesajı göster
                    $('#pubmed-scheduled-search-success').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-success').show();
                    
                    // Satırı kaldır
                    $row.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Tablo boşsa bilgi mesajı göster
                        if ($('.pubmed-health-importer-search-list table tbody tr').length === 0) {
                            $('.pubmed-health-importer-search-list table').replaceWith(
                                '<div class="notice notice-info"><p>Henüz zamanlanmış arama eklenmedi.</p></div>'
                            );
                        }
                    });
                } else {
                    $('#pubmed-scheduled-search-error').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-scheduled-search-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-scheduled-search-error').show();
            }
        });
    });
    
    // Zamanlanmış arama çalıştırma butonu
    $('.pubmed-scheduled-search-run-button').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var id = $button.data('id');
        
        // Bildirimleri ve sonuçları temizle
        $('#pubmed-scheduled-search-success, #pubmed-scheduled-search-error').hide();
        $('#pubmed-scheduled-search-run-results').hide();
        $('#pubmed-scheduled-search-results-list').empty();
        
        // Spinner göster
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        
        // AJAX isteği gönder
        $.ajax({
            url: pubmed_health_importer.ajax_url,
            type: 'POST',
            data: {
                action: 'pubmed_run_scheduled_search',
                nonce: pubmed_health_importer.nonce,
                id: id
            },
            success: function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    // Başarı mesajı göster
                    $('#pubmed-scheduled-search-success').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-success').show();
                    
                    // Sonuçları göster
                    if (response.data.results && response.data.results.articles && response.data.results.articles.length > 0) {
                        $('#pubmed-scheduled-search-results-count').html('<p>' + response.data.results.total + ' sonuç bulundu.</p>');
                        
                        $.each(response.data.results.articles, function(index, article) {
                            var html = '<div class="pubmed-result-item">';
                            html += '<h3>' + article.title + '</h3>';
                            html += '<div class="pubmed-result-meta">';
                            html += '<span class="pubmed-result-authors">' + article.authors.join(', ') + '</span>';
                            html += '<span class="pubmed-result-journal">' + article.journal + '</span>';
                            html += '<span class="pubmed-result-date">' + article.publication_date + '</span>';
                            html += '</div>';
                            html += '</div>';
                            
                            $('#pubmed-scheduled-search-results-list').append(html);
                        });
                        
                        $('#pubmed-scheduled-search-run-results').show();
                    }
                    
                    // Sayfayı yenile
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    $('#pubmed-scheduled-search-error').find('p').text(response.data.message);
                    $('#pubmed-scheduled-search-error').show();
                }
            },
            error: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                $('#pubmed-scheduled-search-error').find('p').text('Bir hata oluştu. Lütfen daha sonra tekrar deneyin.');
                $('#pubmed-scheduled-search-error').show();
            }
        });
    });
});
