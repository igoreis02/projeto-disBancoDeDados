document.addEventListener('DOMContentLoaded', function () {
    const formularioCadastroEndereco = document.getElementById('formularioCadastroEndereco');
    const mensagemDivEndereco = document.getElementById('mensagemEndereco');
    const urlParams = new URLSearchParams(window.location.search);
    const telefone = urlParams.get('telefone');

    // Preenche o campo de telefone hidden
    document.getElementById('telefoneEndereco').value = telefone;

    const enderecoInput = document.getElementById('endereco');
    const quadraInput = document.getElementById('quadra');
    const loteInput = document.getElementById('lote');
    const setorInput = document.getElementById('setor');
    const complementoInput = document.getElementById('complemento');
    const cidadeInput = document.getElementById('cidade');

    // Get hidden latitude and longitude inputs
    const latitudeCadastroInput = document.getElementById('latitudeCadastro');
    const longitudeCadastroInput = document.getElementById('longitudeCadastro');

    let mapInitialized = false; // Flag to ensure map is only initialized once
    let map; // Declare map variable outside to be accessible
    let marker; // Declare marker variable outside to be accessible

    // Function to initialize the map
    function initializeMap() {
        if (!mapInitialized) {
            // Inicializa o mapa com a vista padrão (Aparecida de Goiânia, GO)
            map = L.map('map').setView([-16.6869, -49.2648], 13); 

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
            }).addTo(map);

            // Tenta obter a localização atual do usuário
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    var lat = position.coords.latitude;
                    var lon = position.coords.longitude;

                    // Define os valores dos inputs hidden de latitude e longitude
                    latitudeCadastroInput.value = lat;
                    longitudeCadastroInput.value = lon;

                    // Define a vista do mapa para a localização do usuário
                    map.setView([lat, lon], 16); 

                    // Adiciona um marcador na localização do usuário
                    marker = L.marker([lat, lon]).addTo(map)
                        .bindPopup('Sua localização atual. Arraste para ajustar.')
                        .openPopup();

                    // Torna o marcador arrastável
                    marker.dragging.enable();
                    marker.on('dragend', function (e) {
                        var newLatLng = marker.getLatLng();
                        // Atualiza os inputs hidden na arrastada do marcador
                        latitudeCadastroInput.value = newLatLng.lat;
                        longitudeCadastroInput.value = newLatLng.lng;
                        reverseGeocode(newLatLng.lat, newLatLng.lng);
                    });

                    // Faz a geocodificação inversa da posição inicial
                    reverseGeocode(lat, lon);

                }, function (error) {
                    console.error("Erro ao obter localização: ", error);
                    mensagemDivEndereco.textContent = 'Não foi possível obter sua localização. Por favor, preencha o endereço manualmente.';
                    mensagemDivEndereco.style.color = 'orange';
                    // Permite preenchimento manual se a geolocalização falhar
                    enderecoInput.removeAttribute('readonly');
                    setorInput.removeAttribute('readonly');
                    cidadeInput.removeAttribute('readonly');
                });
            } else {
                mensagemDivEndereco.textContent = 'Geolocalização não é suportada por este navegador. Por favor, preencha o endereço manualmente.';
                mensagemDivEndereco.style.color = 'orange';
                // Permite preenchimento manual se a geolocalização não for suportada
                enderecoInput.removeAttribute('readonly');
                setorInput.removeAttribute('readonly');
                cidadeInput.removeAttribute('readonly');
            }
            mapInitialized = true;
        }
        
        // AQUI ESTÁ A CORREÇÃO: Chama invalidateSize com um pequeno atraso
        // Isso dá tempo para o DOM renderizar e para o CSS aplicar as dimensões finais ao mapa.
        setTimeout(function() {
            if (map) { // Garante que o objeto map já foi criado
                map.invalidateSize();
            }
        }, 100); // 100 milissegundos de atraso
    }

    // Function to reverse geocode coordinates using Nominatim
    function reverseGeocode(lat, lon) {
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
            .then(response => response.json())
            .then(data => {
                if (data.address) {
                    enderecoInput.value = data.address.road || '';
                    setorInput.value = data.address.suburb || data.address.neighbourhood || data.address.village || data.address.town || '';
                    cidadeInput.value = data.address.city || data.address.town || data.address.village || '';
                }
            })
            .catch(error => {
                console.error("Erro ao realizar geocodificação reversa:", error);
                mensagemDivEndereco.textContent = 'Erro ao buscar endereço para a localização. Preencha manualmente.';
                mensagemDivEndereco.style.color = 'red';
            });
    }

    // Inicializa o mapa quando a página carrega
    initializeMap();


    formularioCadastroEndereco.addEventListener('submit', function (event) {
        event.preventDefault(); // Impede o envio do formulário padrão  
        let camposValidos = true;

        // Validate address fields
        const quadraInput = document.getElementById('quadra');
        const loteInput = document.getElementById('lote');

        mensagemDivEndereco.textContent = ''; // Limpa mensagens anteriores
        mensagemDivEndereco.style.color = 'red';

        if (enderecoInput.value.trim() === '') {
            mensagemDivEndereco.textContent = 'Por favor, preencha seu endereço.';
            camposValidos = false;
        } else if (quadraInput.value.trim() === '') {
            mensagemDivEndereco.textContent = 'Por favor, preencha a quadra.';
            camposValidos = false;
        } else if (loteInput.value.trim() === '') {
            mensagemDivEndereco.textContent = 'Por favor, preencha o lote.';
            camposValidos = false;
        } else if (setorInput.value.trim() === '') {
            mensagemDivEndereco.textContent = 'Por favor, preencha o setor.';
            camposValidos = false;
        } else if (cidadeInput.value.trim() === '') {
            mensagemDivEndereco.textContent = 'Por favor, preencha a cidade.';
            camposValidos = false;
        }

        if (camposValidos) {
            formularioCadastroEndereco.submit(); // Envia o formulário se tudo estiver válido
        }
    });

        if (camposValidos) {
            // Submit the form
            const formData = new FormData(formularioCadastroEndereco);

            fetch('atualizar_endereco.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Check if the response is a redirect (status 3xx)
                if (response.redirected) {
                    // This means the PHP script successfully redirected to pedido.html
                    // Replace the current history state to prevent going back to cadastro_pessoal.html
                    // The new URL in the history will be pedido.html
                    window.history.replaceState(null, '', response.url);
                    window.location.replace(response.url); // Actually navigate to the redirected URL
                } else {
                    // If not a redirect, read the response as text and display error
                    return response.text().then(text => {
                        mensagemDivEndereco.innerHTML = text; // Display the error message from PHP
                    });
                }
            })
            .catch(error => {
                console.error('Erro na requisição:', error);
                mensagemDivEndereco.textContent = 'Erro de comunicação com o servidor ao finalizar o cadastro.';
            });
        }
});