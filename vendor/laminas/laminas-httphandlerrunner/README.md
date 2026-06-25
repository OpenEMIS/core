# laminas-httphandlerrunner

[![Build Status](https://github.com/laminas/laminas-httphandlerrunner/workflows/Continuous%20Integration/badge.svg)](https://github.com/laminas/laminas-httphandlerrunner/actions?query=workflow%3A"Continuous+Integration")

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

This library provides utilities for:

- Emitting [PSR-7](https://www.php-fig.org/psr/psr-7) responses.
- Running [PSR-15](https://www.php-fig.org/psr/psr-15) server request handlers,
  which involves marshaling a PSR-7 `ServerRequestInterface`, handling
  exceptions due to request creation, and emitting the response returned by the
  composed request handler.

The `RequestHandlerRunner` will be used in the bootstrap of your application to
fire off the `RequestHandlerInterface` representing your application.

## Installation

Run the following to install this library:

```bash
$ composer require laminas/laminas-httphandlerrunner
```

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
$ mkdocs build
```

You may also [browse the documentation online](https://docs.laminas.dev/laminas-httphandlerrunner/).
