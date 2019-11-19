# -*- coding: utf-8 -*-

# PLEASE DO NOT EDIT THIS FILE, IT IS GENERATED AND WILL BE OVERWRITTEN:
# https://github.com/ccxt/ccxt/blob/master/CONTRIBUTING.md#how-to-contribute-code

from ccxt.base.exchange import Exchange
import base64
from ccxt.base.errors import ExchangeError
from ccxt.base.errors import AuthenticationError
from ccxt.base.errors import ArgumentsRequired
from ccxt.base.errors import BadRequest
from ccxt.base.errors import InsufficientFunds
from ccxt.base.errors import InvalidOrder
from ccxt.base.errors import NotSupported
from ccxt.base.errors import ExchangeNotAvailable


class probit(Exchange):

    def describe(self):
        return self.deep_extend(super(probit, self).describe(), {
            'id': 'probit',
            'name': 'ProBit',
            'countries': ['SC', 'KR'],
            'rateLimit': 250,  # ms
            'has': {
                'CORS': True,
                'fetchTime': True,
                'fetchMarkets': True,
                'fetchCurrencies': True,
                'fetchTickers': True,
                'fetchTicker': True,
                'fetchOHLCV': True,
                'fetchOrderBook': True,
                'fetchTrades': True,
                'fetchBalance': True,
                'createOrder': True,
                'createLimitOrder': True,
                'createMarketOrder': True,
                'cancelOrder': True,
                'fetchOrder': True,
                'fetchOpenOrders': True,
                'fetchClosedOrders': True,
                'fetchMyTrades': True,
                'fetchDepositAddress': True,
                'withdraw': True,
            },
            'timeframes': {
                '1m': '1m',
                '3m': '3m',
                '5m': '5m',
                '10m': '10m',
                '15m': '15m',
                '30m': '30m',
                '1h': '1h',
                '4h': '4h',
                '6h': '6h',
                '12h': '12h',
                '1d': '1D',
                '1w': '1W',
            },
            'version': 'v1',
            'urls': {
                'logo': 'https://static.probit.com/landing/assets/images/probit-logo-global.png',
                'api': {
                    'account': 'https://accounts.probit.com',
                    'exchange': 'https://api.probit.com/api/exchange',
                },
                'www': 'https://www.probit.com',
                'doc': [
                    'https://docs-en.probit.com',
                    'https://docs-ko.probit.com',
                ],
                'fees': 'https://support.probit.com/hc/en-us/articles/360020968611-Trading-Fees',
            },
            'api': {
                'public': {
                    'get': ['market', 'currency', 'time', 'ticker', 'order_book', 'trade', 'candle'],
                },
                'private': {
                    'post': ['new_order', 'cancel_order', 'withdrawal'],
                    'get': ['balance', 'order', 'open_order', 'order_history', 'trade_history', 'deposit_address'],
                },
                'auth': {
                    'post': ['token'],
                },
            },
            'fees': {
                'trading': {
                    'tierBased': False,
                    'percentage': True,
                    'maker': 0.2 / 100,
                    'taker': 0.2 / 100,
                },
            },
            'exceptions': {
                'INVALID_ARGUMENT': BadRequest,
                'TRADING_UNAVAILABLE': ExchangeNotAvailable,
                'NOT_ENOUGH_BALANCE': InsufficientFunds,
                'NOT_ALLOWED_COMBINATION': BadRequest,
                'INVALID_ORDER': InvalidOrder,
            },
            'requiredCredentials': {
                'apiKey': True,
                'secret': True,
            },
            'options': {
                'defaultLimitOrderTimeInForce': 'gtc',
                'defaultMarketOrderTimeInForce': 'ioc',
            },
        })

    def fetch_markets(self, params={}):
        response = self.publicGetMarket()
        markets = self.safe_value(response, 'data')
        result = []
        for i in range(0, len(markets)):
            market = markets[i]
            base = market['base_currency_id']
            quote = market['quote_currency_id']
            symbol = base + '/' + quote
            closed = market['closed']
            result.append({
                'id': market['id'],
                'symbol': symbol,
                'base': base,
                'quote': quote,
                'baseId': base,
                'quoteId': quote,
                'active': not closed,
                'precision': {
                    'amount': market['quantity_precision'],
                    'cost': market['cost_precision'],
                },
                'limits': {
                    'amount': {
                        'min': market['min_quantity'],
                        'max': market['max_quantity'],
                    },
                    'price': {
                        'min': market['min_price'],
                        'max': market['max_price'],
                    },
                    'cost': {
                        'min': market['min_cost'],
                        'max': market['max_cost'],
                    },
                },
                'info': market,
            })
        return result

    def fetch_currencies(self, params={}):
        response = self.publicGetCurrency()
        currencies = self.safe_value(response, 'data')
        result = {}
        for i in range(0, len(currencies)):
            currency = currencies[i]
            code = self.safe_currency_code(currency['id'])
            result[code] = {
                'id': code,
                'code': code,
                'name': currency['name'],
                'info': currency,
                'precision': currency['precision'],
            }
        return result

    def fetch_balance(self, params={}):
        self.load_markets()
        response = self.privateGetBalance()
        balances = self.safe_value(response, 'data')
        result = {'info': balances}
        for i in range(0, len(balances)):
            balance = balances[i]
            currencyId = balance['currency_id']
            account = self.account()
            total = self.safe_float(balance, 'total')
            available = self.safe_float(balance, 'available')
            hold = self.sum(total, -available)
            account['total'] = total
            account['free'] = available
            account['used'] = hold
            result[currencyId] = account
        return self.parse_balance(result)

    def fetch_order_book(self, symbol, limit=None, params={}):
        self.load_markets()
        market = self.market(symbol)
        request = {
            'market_id': market['id'],
        }
        response = self.publicGetOrderBook(self.extend(request, params))
        orderbook = self.safe_value(response, 'data')
        return self.parse_order_book(orderbook, None, 'buy', 'sell', 'price', 'quantity')

    def parse_order_book(self, orderbook, timestamp=None, bidsKey='buy', asksKey='sell', priceKey='price', amountKey='quantity'):
        bids = []
        asks = []
        for i in range(0, len(orderbook)):
            item = orderbook[i]
            if item['side'] == bidsKey:
                bids.append(item)
            elif item['side'] == asksKey:
                asks.append(item)
        return {
            'bids': self.sort_by(self.parse_bids_asks(bids, priceKey, amountKey), 0, True),
            'asks': self.sort_by(self.parse_bids_asks(asks, priceKey, amountKey), 0),
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'nonce': None,
        }

    def fetch_tickers(self, symbols=None, params={}):
        self.load_markets()
        marketIds = []
        for i in range(0, len(symbols)):
            market = self.market(symbols[i])
            marketIds.append(market['id'])
        request = {
            'market_ids': ','.join(marketIds),
        }
        response = self.publicGetTicker(self.extend(request, params))
        return self.parse_tickers(response['data'], symbols)

    def parse_tickers(self, rawTickers, symbols=None):
        tickers = []
        for i in range(0, len(rawTickers)):
            tickers.append(self.parse_ticker(rawTickers[i]))
        return self.filter_by_array(tickers, 'symbol', symbols)

    def fetch_ticker(self, symbol, params={}):
        self.load_markets()
        market = self.market(symbol)
        request = {
            'market_ids': market['id'],
        }
        response = self.publicGetTicker(self.extend(request, params))
        return self.parse_ticker(self.safe_value(response, 'data')[0], market)

    def parse_ticker(self, ticker, market=None):
        timestamp = self.parse8601(self.safe_string(ticker, 'time'))
        symbol = self.find_symbol(self.safe_string(ticker, 'market_id'), market)
        last = self.safe_float(ticker, 'last')
        return {
            'close': last,
            'last': last,
            'low': self.safe_float(ticker, 'low'),
            'high': self.safe_float(ticker, 'high'),
            'change': self.safe_float(ticker, 'change'),
            'baseVolume': self.safe_float(ticker, 'base_volume'),
            'quoteVolume': self.safe_float(ticker, 'quote_volume'),
            'symbol': symbol,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'info': ticker,
            'bid': None,
            'bidVolume': None,
            'ask': None,
            'askVolume': None,
            'vwap': None,
            'open': None,
            'previousClose': None,
            'percentage': None,
            'average': None,
        }

    def fetch_my_trades(self, symbol=None, since=None, limit=None, params={}):
        self.load_markets()
        market = None
        request = {
            'limit': 100,
            'start_time': self.iso8601(0),
            'end_time': self.iso8601(self.milliseconds()),
        }
        if symbol is not None:
            market = self.market(symbol)
            request['market_id'] = market['id']
        if since is not None:
            request['start_time'] = self.iso8601(since)
        if limit is not None:
            request['limit'] = limit
        response = self.privateGetTradeHistory(self.extend(request, params))
        return self.parse_trades(response['data'], market, since, limit)

    def fetch_trades(self, symbol, since=None, limit=None, params={}):
        self.load_markets()
        market = self.market(symbol)
        request = {
            'market_id': market['id'],
            'limit': 100,
            'start_time': self.iso8601(0),
            'end_time': self.iso8601(self.milliseconds()),
        }
        if since is not None:
            request['start_time'] = self.iso8601(since)
        if limit is not None:
            request['limit'] = limit
        response = self.publicGetTrade(self.extend(request, params))
        return self.parse_trades(response['data'], market, since, limit)

    def parse_trade(self, trade, market=None):
        time = self.safe_string(trade, 'time')
        timestamp = self.parse8601(time)
        symbol = self.safe_string(market, 'symbol')
        fee = None
        feeAmount = self.safe_float(trade, 'fee_amount')
        if feeAmount is not None and feeAmount != 0:
            fee = {
                'currency': self.safe_string(trade, 'fee_currency_id'),
                'cost': feeAmount,
            }
        return {
            'id': self.safe_string(trade, 'id'),
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'symbol': symbol,
            'order': self.safe_string(trade, 'order_id'),
            'type': None,
            'side': self.safe_string(trade, 'side'),
            'takerOrMaker': None,
            'price': self.safe_float(trade, 'price'),
            'amount': self.safe_float(trade, 'quantity'),
            'cost': self.safe_float(trade, 'cost'),
            'fee': fee,
            'info': trade,
        }

    def fetch_time(self, params={}):
        response = self.publicGetTime()
        timestamp = self.parse8601(self.safe_string(response, 'data'))
        return timestamp

    def normalize_candle_timestamp(self, timestamp, timeframe):
        coeff = int(timeframe[0:-1], 10)
        unitLetter = timeframe[-1:]
        m = 60 * 1000
        h = 60 * m
        D = 24 * h
        W = 7 * D
        units = {
            'm': m,
            'h': h,
            'D': D,
            'W': W,
        }
        unit = units[unitLetter]
        mod = coeff * unit
        diff = (timestamp % mod)
        timestamp = timestamp - diff
        if unit == W:
            timestamp = timestamp + 3 * D
        return timestamp

    def fetch_ohlcv(self, symbol, timeframe='1m', since=None, limit=None, params={}):
        self.load_markets()
        market = self.market(symbol)
        interval = self.timeframes[timeframe]
        if not interval:
            raise NotSupported('Timeframe ' + timeframe + ' is not supported.')
        request = {
            'market_ids': market['id'],
            'interval': interval,
            'start_time': self.iso8601(self.normalize_candle_timestamp(0, interval)),
            'end_time': self.iso8601(self.normalize_candle_timestamp(self.milliseconds(), interval)),
            'sort': 'desc',
            'limit': 100,
        }
        if since is not None:
            request['start_time'] = self.iso8601(self.normalize_candle_timestamp(since, interval))
        if limit is not None:
            request['limit'] = limit
        response = self.publicGetCandle(self.extend(request, params))
        data = self.safe_value(response, 'data')
        return self.parse_ohlcvs(data, market, timeframe, since, limit)

    def parse_ohlcv(self, ohlcv, market=None, timeframe='1m', since=None, limit=None):
        return [
            float(self.parse8601(self.safe_string(ohlcv, 'start_time'))),
            float(self.safe_float(ohlcv, 'open')),
            float(self.safe_float(ohlcv, 'high')),
            float(self.safe_float(ohlcv, 'low')),
            float(self.safe_float(ohlcv, 'close')),
            float(self.safe_float(ohlcv, 'base_volume')),
        ]

    def fetch_open_orders(self, symbol=None, since=None, limit=None, params={}):
        self.load_markets()
        since = self.parse8601(since)
        request = {}
        if symbol:
            request['market_id'] = self.market_id(symbol)
        resp = self.privateGetOpenOrder(self.extend(request, params))
        orders = self.safe_value(resp, 'data')
        arr = []
        for i in range(0, len(orders)):
            if since and self.parse8601(orders[i]['time']) < since:
                continue
            symbol = self.find_symbol(self.safe_string(orders[i], 'market_id'))
            arr.append(self.parse_order(orders[i], symbol))
        # order by desc
        arr = self.sort_by(arr, 'timestamp', True)
        if limit:
            arr = arr[0:limit]
        return arr

    def fetch_closed_orders(self, symbol=None, since=None, limit=None, params={}):
        self.load_markets()
        request = {
            'start_time': self.iso8601(0),
            'end_time': self.iso8601(self.milliseconds()),
            'limit': 100,
        }
        if symbol:
            request['market_id'] = self.market_id(symbol)
        if since:
            request['start_time'] = self.iso8601(since)
        if limit:
            request['limit'] = limit
        resp = self.privateGetOrderHistory(self.extend(request, params))
        orders = self.safe_value(resp, 'data')
        arr = []
        for i in range(0, len(orders)):
            symbol = self.find_symbol(self.safe_string(orders[i], 'market_id'))
            arr.append(self.parse_order(orders[i], symbol))
        return arr

    def fetch_order(self, id, symbol=None, params={}):
        if symbol is None:
            raise ArgumentsRequired(self.id + ' fetchOrder requires a symbol argument')
        self.load_markets()
        market = self.market(symbol)
        request = {
            'market_id': market['id'],
            'order_id': str(id),
        }
        clientOrderId = self.safe_string(params, 'clientOrderId')
        if clientOrderId:
            request['client_order_id'] = clientOrderId
        response = self.privateGetOrder(self.extend(request, params))
        order = self.safe_value(response, 'data')[0]
        return self.parse_order(order, symbol)

    def parse_order(self, order, symbol):
        status = order['status']
        if status == 'filled':
            status = 'closed'
        time = order['time']
        filledCost = self.safe_float(order, 'filled_cost')
        filledQty = self.safe_float(order, 'filled_quantity')
        openQty = self.safe_float(order, 'open_quantity')
        qty = self.safe_float(order, 'quantity')
        price = self.safe_float(order, 'limit_price')
        if order['type'] == 'market':
            qty = self.sum(filledQty, openQty)
            if filledCost > 0 and filledQty > 0:
                price = filledCost / filledQty
        return {
            'id': order['id'],
            'symbol': symbol,
            'type': order['type'],
            'side': order['side'],
            'datetime': time,
            'timestamp': self.parse8601(time),
            'lastTradeTimestamp': None,
            'status': status,
            'price': price,
            'amount': qty,
            'filled': filledQty,
            'remaining': openQty,
            'cost': filledCost,
            'info': order,
        }

    def create_order(self, symbol, type, side, amount, price=None, params={}):
        self.load_markets()
        market = self.market(symbol)
        req = {
            'market_id': market['id'],
            'type': type,
            'side': side,
        }
        clientOrderId = self.safe_string(params, 'clientOrderId')
        if clientOrderId:
            req['client_order_id'] = clientOrderId
        timeInForce = self.safe_string(params, 'timeInForce')
        if type == 'limit':
            if not timeInForce:
                timeInForce = self.options['defaultLimitOrderTimeInForce']
            req['time_in_force'] = timeInForce
            req['limit_price'] = self.price_to_precision(symbol, price)
            req['quantity'] = self.amount_to_precision(symbol, amount)
        elif type == 'market':
            if not timeInForce:
                timeInForce = self.options['defaultMarketOrderTimeInForce']
            req['time_in_force'] = timeInForce
            if side == 'sell':
                req['quantity'] = self.amount_to_precision(symbol, amount)
            elif side == 'buy':
                req['cost'] = self.cost_to_precision(symbol, amount)
        resp = self.privatePostNewOrder(self.extend(req, params))
        return self.parse_order(self.safe_value(resp, 'data'), symbol)

    def cancel_order(self, id, symbol=None, params={}):
        market = self.market(symbol)
        request = {
            'market_id': market['id'],
            'order_id': str(id),
        }
        resp = self.privatePostCancelOrder(self.extend(request, params))
        return self.parse_order(self.safe_value(resp, 'data'))

    def parse_deposit_address(self, depositAddress, currency=None):
        address = self.safe_string(depositAddress, 'address')
        tag = self.safe_string(depositAddress, 'destination_tag')
        currencyId = self.safe_string(depositAddress, 'currency_id')
        code = self.safe_currency_code(currencyId)
        self.check_address(address)
        return {
            'currency': code,
            'address': address,
            'tag': tag,
            'info': depositAddress,
        }

    def create_deposit_address(self, code, params={}):
        return self.fetch_deposit_address(code, params)

    def fetch_deposit_address(self, code, params={}):
        self.load_markets()
        currency = self.currency(code)
        request = {
            'currency_id': currency['id'],
        }
        response = self.privateGetDepositAddress(self.extend(request, params))
        return self.parse_deposit_address(response['data'][0])

    def fetch_deposit_addresses(self, codes=None, params={}):
        self.load_markets()
        request = {}
        if codes:
            currencyIds = []
            for i in range(0, len(codes)):
                currency = self.currency(codes[i])
                currencyIds.append(currency['id'])
            request['currency_id'] = ','.join(codes)
        response = self.privateGetDepositAddress(self.extend(request, params))
        return self.parse_deposit_addresses(response['data'])

    def parse_deposit_addresses(self, rawAddresses):
        addresses = []
        for i in range(0, len(rawAddresses)):
            addresses.append(self.parse_deposit_address(rawAddresses[i]))
        return addresses

    def withdraw(self, code, amount, address, tag=None, params={}):
        self.check_address(address)
        self.load_markets()
        currency = self.currency(code)
        if not tag:
            tag = None
        request = {
            'currency_id': currency['id'],
            'address': address,
            'destination_tag': tag,
            'amount': self.number_to_string(amount),
        }
        response = self.privatePostWithdrawal(self.extend(request, params))
        return self.parse_transaction(response['data'])

    def parse_transaction(self, transaction, currency=None):
        id = self.safe_string(transaction, 'id')
        amount = self.safe_float(transaction, 'amount')
        address = self.safe_string(transaction, 'address')
        tag = self.safe_string(transaction, 'destination_tag')
        txid = self.safe_string(transaction, 'hash')
        timestamp = self.parse8601(self.safe_string(transaction, 'time'))
        type = self.safe_string(transaction, 'type')
        currencyId = self.safe_string(transaction, 'currency_id')
        code = self.safe_currency_code(currencyId)
        status = self.parse_transaction_status(self.safe_string(transaction, 'status'))
        feeCost = self.safe_float(transaction, 'fee')
        fee = None
        if feeCost is not None and feeCost != 0:
            fee = {
                'currency': code,
                'cost': feeCost,
            }
        return {
            'id': id,
            'currency': code,
            'amount': amount,
            'address': address,
            'tag': tag,
            'status': status,
            'type': type,
            'txid': txid,
            'timestamp': timestamp,
            'datetime': self.iso8601(timestamp),
            'fee': fee,
            'info': transaction,
        }

    def parse_transaction_status(self, status):
        statuses = {
            'requested': 'pending',
            'pending': 'pending',
            'confirming': 'pending',
            'confirmed': 'pending',
            'applying': 'pending',
            'done': 'ok',
            'cancelled': 'canceled',
            'cancelling': 'canceled',
        }
        return self.safe_string(statuses, status, status)

    def nonce(self):
        return self.milliseconds()

    def sign(self, path, api='public', method='GET', params={}, headers=None, body=None):
        url = self.urls['api']['exchange'] + '/' + self.version + '/'
        if api == 'auth':
            url = self.urls['api']['account'] + '/'
        query = self.omit(params, self.extract_params(path))
        if api == 'public':
            url += self.implode_params(path, params)
            if query:
                url += '?' + self.urlencode(query)
        elif api == 'private':
            self.check_required_credentials()
            expires = self.safe_integer(self.options, 'expires')
            if not expires or expires < self.milliseconds():
                raise AuthenticationError(self.id + ' accessToken expired, call signIn() method')
            url += self.implode_params(path, params)
            if method == 'GET':
                if query:
                    url += '?' + self.urlencode(query)
            elif query:
                body = self.json(query)
            headers = {
                'Authorization': 'Bearer ' + self.options['accessToken'],
                'Content-Type': 'application/json',
            }
        elif api == 'auth':
            self.check_required_credentials()
            url += self.implode_params(path, params)
            encoded = self.encode(self.apiKey + ':' + self.secret)
            basicAuth = base64.b64encode(encoded)
            headers = {
                'Authorization': 'Basic ' + self.decode(basicAuth),
                'Content-Type': 'application/json',
            }
            if method == 'GET':
                if query:
                    url += '?' + self.urlencode(query)
            elif query:
                body = self.json(query)
        return {'url': url, 'method': method, 'body': body, 'headers': headers}

    def sign_in(self, params={}):
        self.check_required_credentials()
        if not self.apiKey or not self.secret:
            raise AuthenticationError(self.id + ' signIn() requires self.apiKey and self.secret credentials')
        body = {
            'grant_type': 'client_credentials',
        }
        tokenResponse = self.authPostToken(body)
        expiresIn = self.safe_integer(tokenResponse, 'expires_in')
        accessToken = self.safe_string(tokenResponse, 'access_token')
        self.options['accessToken'] = accessToken
        self.options['expires'] = self.sum(self.milliseconds(), expiresIn * 1000)
        self.options['tokenType'] = self.safe_string(tokenResponse, 'token_type')
        return tokenResponse

    def handle_errors(self, httpCode, reason, url, method, headers, body, response):
        if response is None:
            return  # fallback to default error handler
        if 'errorCode' in response:
            errorCode = self.safe_string(response, 'errorCode')
            message = self.safe_string(response, 'message')
            if errorCode is not None:
                feedback = self.json(response)
                exceptions = self.exceptions
                if message in exceptions:
                    raise exceptions[message](feedback)
                elif errorCode in exceptions:
                    raise exceptions[errorCode](feedback)
                else:
                    raise ExchangeError(feedback)
