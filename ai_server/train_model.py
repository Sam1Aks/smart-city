import pandas as pd
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report
from sklearn.preprocessing import LabelEncoder

# Загрузка данных
data = pd.read_csv('data_for_training.csv')

# Предобработка данных
# Преобразуем категориальные данные в числовые
le_sensor_type = LabelEncoder()
data['sensor_type'] = le_sensor_type.fit_transform(data['sensor_type'])

# Разделим данные на признаки и целевую переменную
X = data[['sensor_type', 'sensor_data']]
y = data['risk']

# Разделим данные на обучающую и тестовую выборки
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.2, random_state=42)

# Обучение модели Random Forest
model = RandomForestClassifier(n_estimators=100, random_state=42)
model.fit(X_train, y_train)

# Оценка качества модели
y_pred = model.predict(X_test)

# Печатаем отчет о точности
print(classification_report(y_test, y_pred))

# Сохраняем модель для дальнейшего использования
import joblib
joblib.dump(model, 'random_forest_model.pkl')

# Создаем DataFrame с нужными столбцами
sample_data = pd.DataFrame([[0, 22.5]], 
                           columns=['sensor_type', 'sensor_data'])

# Теперь передаем sample_data в модель для предсказания
predicted_risk = model.predict(sample_data)

# Выводим предсказанный риск
print(f'Predicted Risk: {predicted_risk[0]}')