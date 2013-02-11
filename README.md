TestJobPCards
=============

### Set
- Need to implement a web-based application that simulates a network game. Early in the game, two or more users to enter the same key in the field provided and their names in the game (a separate field for the name).
- After entering the key on each user's screen displays a set of 10 random cards. Each card has two states: "open" and "closed". Random set of cards assigned to the entered key. Different card users may be repeated. When the user clicks on the map, it should change its state - over. This change should be seen every user in the game. In addition, you want to show ip-addresses of all users involved in the game this time and their names.
- Need to keep a log of all actions performed with user IDs.
- Coup card you want to animate by means of CSS with JS.
- Texture maps and shirts have to find yourself.
- Offer the most effective methods of storing data on the server.
- As a result of the job from you, we want to get a reference to a Web application in which you can test the game and source archives.
- Period of execution of test task is 3 days of receipt.

### Results
v 1.0.0
1. done in about 16 hours
2. used to continuously update JS function setInterval, but in the working draft is not recommended to look towards better NodeJS, not used since before that did not work with NodeJS
3. use memcache to cache results, connect to the database in the main to update / write
4. used for design TwitterBootstrap
5. also the working draft to analyze in more detail the architecture of the application, the application is made only for testing, and the time was limited

v 2.0.0
1. done in about plus ~8 hours
2. add nodejs
3. reduce server requests
4. use socket.io
5. memcached used for authentication, the output of users online and check for back-end

v 2.0.1
1. add validate

<!--
###Задание
Необходимо реализовать веб-приложение, симулирующее сетевую игру. В начале игры два или более пользователей вводят одинаковый ключ в предоставленное поле и свои имена в игре (отдельное поле для ввода имени).
После ввода ключа на экране каждого из пользователей отображается набор из 10-ти случайных карт. У каждой карты есть 2 состояния: “открыто” и “закрыто”. Случайный набор карт закрепляется за введённым ключом. У разных пользователей карты могут повторяться. При нажатии пользователем на карту она должна изменить своё состояние  - перевернуться. Это изменение должен увидеть каждый пользователь, находящийся в игре. Кроме того, необходимо отображать ip-адреса всех пользователей  участвующих в игре данный момент и их имена.
Необходимо вести лог всех выполненных действий с идентификаторами пользователей.
Переворот карт требуется анимировать при помощи средств CSS с использованием JS.
Текстуры карт и рубашки нужно найти самостоятельно.
Предложить наиболее эффективные методы хранения данных на сервере.
В качестве результата выполнения задания от Вас мы хотим получить ссылку на веб-приложение, в котором можно будет проверить работу игры и архив с исходниками.
Срок выполнения тестового задания составляет 3 дня с момента его получения.

###Результаты
сделано примерно за 16 часов
для постоянного обновления использовалась JS функция setInterval, но на рабочем проекте не рекомендуется лучше смотреть в сторону NodeJS, не использовал поскольку до этого не работал с NodeJS
использовал memcache для кеширования результатов подключаюсь к базе в основном для обновления/записи
для дизайна использовал TwitterBootstrap
также на рабочем проекте необходимо более подробно продумывать архитектуру приложения, данное приложение сделано только для тестов, и время было ограничено
-->
