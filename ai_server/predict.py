import joblib
import pandas as pd
from sklearn.preprocessing import LabelEncoder

# Загружаем модель
model = joblib.load('random_forest_model.pkl')

# Чтение данных из нового CSV
data = pd.read_csv('new.csv')

le_sensor_type = LabelEncoder()
data['sensor_type'] = le_sensor_type.fit_transform(data['sensor_type'])

# Проверка данных (при необходимости обработка)
# Если в данных есть столбцы, которые не нужны (например, неиспользуемые параметры),
# то их можно удалить. Также, может быть необходимо преобразовать некоторые данные.
data_cleaned = data[['sensor_type', 'sensor_data']]  # Оставляем только нужные столбцы

# Прогнозирование риска для каждого значения в новом CSV
predictions = model.predict(data_cleaned)

# Добавляем столбец с предсказанным статусом
data['predicted_risk'] = predictions

# Запись обновленных данных обратно в CSV (можно перезаписать или сохранить в новый файл)
data.to_csv('new_with_risk.csv', index=False)

# Вывод примера
print(data[['sensor_type', 'sensor_data', 'predicted_risk']].head())
