ymaps.ready(init);
var myMap;

function init() {
    myMap = new ymaps.Map("map", {
        center: [51.114277, 71.421904],
        zoom: 16
    });

    console.log("Запрос к add_sensors.php...");

    fetch('add_sensors.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`Ошибка HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log("Response from the server:", data);

            Object.keys(data.buildings).forEach(buildingId => {
                let status = data.buildings[buildingId];
                let color = getBuildingColor(status);
                let coords = getBuildingCoords(buildingId);

                var placemark = new ymaps.Placemark(coords, {}, {
                    preset: 'islands#circleIcon',
                    iconColor: color
                });

                placemark.events.add('click', function () {
                    console.log(`Building entrance statuses ${buildingId}:`, data.entrances);

                    let entranceListHtml = '';
                    Object.keys(data.entrances).forEach(entranceId => {
                        if (entranceId.startsWith(buildingId + "-")) {
                            let entranceStatus = data.entrances[entranceId];
                            let entranceColor = getBuildingColor(entranceStatus);

                            entranceListHtml += `
                                <li style="display: flex; align-items: center; gap: 8px; margin: 5px 0;">
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background-color: ${entranceColor};"></div>
                                    <a href="sensors.html?building=${buildingId}&entrance=${entranceId.split('-')[1]}" style="text-decoration: none; color: black;">
                                        Entrance ${entranceId.split('-')[1]}
                                    </a>
                                </li>`;
                        }
                    });

                    if (entranceListHtml === '') {
                        entranceListHtml = '<li>Нет данных о подъездах</li>';
                    }

                    myMap.balloon.open(coords, {
                        contentHeader: `Building ${buildingId}`,
                        contentBody: `<ul style="list-style: none; padding: 0;">${entranceListHtml}</ul>`
                    });
                });

                myMap.geoObjects.add(placemark);
            });
        })
        .catch(error => console.error("Ошибка загрузки данных:", error));
}

function getBuildingColor(status) {
    if (status === "red") return "red";
    if (status === "yellow") return "yellow";
    return "green";
}

// Функция для получения координат здания
function getBuildingCoords(buildingId) {
    const coords = {
        1: [51.114277, 71.421904],
        2: [51.113711, 71.422210],
        3: [51.113298, 71.421491] 
    };
    return coords[buildingId] || [51.114277, 71.421904];
}
