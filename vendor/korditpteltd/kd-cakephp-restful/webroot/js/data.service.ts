import { Injectable } from '@angular/core';
import { Http, Response, Headers, RequestOptions, RequestMethod } from '@angular/http';
import 'rxjs/add/operator/map';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class DataService {
    private base: string;
    private _action: string = null;
    private version: string = 'v2';
    private controller = 'restful';
    private className: string;
    private responseType: string = 'json';
    private method = 'GET';
    private id: any = 0;
    private headers: any = {};
    private _schema: boolean = false;
    private _fields: Array<string> = [];
    private _contain: any = [];
    private _innerJoinWith: any = [];
    private _leftJoinWith: any = [];
    private _finder: Array<string> = [];
    private _where: Object = {};
    private _orWhere: any = [];
    private _group: any = [];
    private _order: any = [];
    private _limit: number = 0;
    private _page: number = 0;
    private _search: string = '';
    private _querystring: string = '';
    private _db = 'default';
    private _showBlobContent: any = null;

    private _wildcard = '_';

    constructor(private http: Http) {}

    init(className: string, action: string = 'custom', controller: string = 'restful') {
        let ds = new DataService(this.http);
        // set base to config file
        ds.setBase(this.base);
        ds.setClassName(className);
        ds.setVersion(this.version);
        ds.setAction(action);
        ds.setDB(this._db);
        ds.setHeader(this.headers);
        ds.setController(controller);
        return ds;
    }

    setController(controller: string) {
        this.controller = controller;
    }

    setVersion(version: string) {
        this.version = version;
    }

    setBase(base: string) {
        this.base = base;
    }

    setDB(db: string) {
        this._db = db;
    }

    urlsafeB64Encode(textStr: string)
    {
        let encoded = encodeURI(btoa(textStr)).replace(/=/gi, "");
        return encoded;
    }

    setClassName(className: string) {
        this.className = className;
    }

    setAction(action: string) {
        this._action = action;
    }

    setHeader(header: any) {
        for (let i in header) {
            this.headers[i] = header[i];
        }
    }

    resetHeader() {
        this.headers = {};
    }

    getHeader() {
        return this.headers;
    }

    wildcard(): string {
        return this._wildcard;
    }

    schema(bool: boolean) {
        this._schema = bool;
        return this;
    }

    querystring(params: any) {
        let json = JSON.stringify(params);
        this._querystring = this.urlsafeB64Encode(json);
        return this;
    }

    reset() {
        this.method = 'GET';
        this.id = 0;
        this._fields = [];
        this._contain = [];
        this._innerJoinWith = [];
        this._leftJoinWith = [];
        this._finder = [];
        this._where = {};
        this._limit = 0;
        this._group = [];
        this._order = [];
        this._page = 0;
        this._schema = false;
        this._querystring = '';
        this._search = '';
    }

    select(fields: Array<string>): DataService {
        if (fields != undefined) {
            this._fields = fields;
        }
        return this;
    }

    get(id: any): Observable<any> {
        this.id = id;
        return this.ajax();
    }

    all(): Observable<any> {
        return this.ajax();
    }

    contain(contain: any): DataService {
        this._contain = contain;
        return this;
    }

    innerJoinWith(innerJoinWith: any): DataService {
        this._innerJoinWith = innerJoinWith;
        return this;
    }

    leftJoinWith(leftJoinWith: any): DataService {
        this._leftJoinWith = leftJoinWith;
        return this;
    }

    find(finder: string, params: Object = undefined): DataService {
        if (params === undefined) {
            this._finder.push(finder);
        } else if (params instanceof Object) {
            let paramsArray = [];
            for (var key in params) {
                paramsArray.push(key + ':' + params[key]);
            }
            this._finder.push(finder + '[' + paramsArray.join(';') + ']');
        }
        return this;
    }

    where(where: Object): DataService {
        let returnArr = {};
        for (let i in where) {
            returnArr[i.replace('.', '-')] = where[i];
        }
        this._where = returnArr;
        return this;
    }

    orWhere(orWhere: any): DataService {
        var paramsArray = [];
        for (var key in orWhere) {
            paramsArray.push(key + ':' + orWhere[key]);
        }
        this._orWhere.push(paramsArray.join(','));
        return this;
    }

    group(group: any): DataService {
        this._group = group;
        return this;
    }

    order(order: any): DataService {
        this._order = order;
        return this;
    }

    limit(limit: number): DataService {
        this._limit = limit;
        return this;
    }

    page(page: number): DataService {
        this._page = page;
        return this;
    }

    search(searchText: string): DataService {
        if (searchText == '') {
            this._search = searchText;
        } else {
            this._search = this.urlsafeB64Encode(searchText);
        }
        return this;
    }

    toURL(responseType: string): string {
        let model = this.className.replace('.', '-');
        let url = [this.base, this.controller, this.version, model].join('/');

        let params = [];

        if (this._action != null) {
            params.push('_action=' + this._action);
        }

        if (this._fields.length > 0) {
            params.push('_fields=' + this._fields.join(','));
        }

        if ((typeof this._contain === 'object')) {
            if (this._contain.length > 0) {
                params.push('_contain=' + this._contain.join(','));
            }
        }

        if ((typeof this._innerJoinWith === 'object')) {
            if (this._innerJoinWith.length > 0) {
                params.push('_innerJoinWith=' + this._innerJoinWith.join(','));
            }
        }

        if ((typeof this._leftJoinWith === 'object')) {
            if (this._leftJoinWith.length > 0) {
                params.push('_leftJoinWith=' + this._leftJoinWith.join(','));
            }
        }

        if (this._finder.length > 0) {
            params.push('_finder=' + this._finder.join(','));
        }
        if (this._group.length > 0) {
            params.push('_group=' + this._group.join(','));
        }
        if (this._order.length > 0) {
            params.push('_order=' + this._order.join(','));
        }
        if (Object.keys(this._where).length > 0) {
            for (var key in this._where) {
                params.push(key + '=' + this._where[key]);
            }
        }
        if (this._orWhere.length > 0) {
            params.push('_orWhere=' + this._orWhere.join(','));
        }
        params.push('_limit=' + this._limit);
        if (this._page > 0) {
            params.push('_page=' + this._page);
        }

        if (this._db != 'default') {
            params.push('_db=' + this._db);
        }

        if (this._showBlobContent != null) {
            params.push('_showBlobContent=' + this._showBlobContent);
        }

        if (this._schema) {
            params.push('_schema=' + this._schema);
        }

        if (this._querystring.length > 0) {
            params.push('_querystring=' + this._querystring);
        }

        if (this._search.length > 0) {
            params.push('_search=' + this._search);
        }

        if (this.id > 0 || typeof this.id == 'string' || this.id == 'schema') {
            url += '/' + this.id;
        }

        if (responseType !== undefined) {
            this.responseType = responseType;
        }
        url += '.' + this.responseType;

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        return url;
    }

    getRequestHeader(): any {
        let requestOptions = new RequestOptions({ headers: this.getHeader() });
        return requestOptions;
    }

    save(data: Object): Observable<any> {
        let requestOptions =  this.getRequestHeader();
        let url = this.toURL(undefined);
        let observable = this.http.post(url, data, requestOptions).map(this.extractData);
        this.reset();
        return observable;
    }

    edit(data: Object): Observable<any> {
        let requestOptions =  this.getRequestHeader();
        let model = this.className.replace('.', '-');
        let url = this.toURL(undefined);
        let observable = this.http.patch(url, data, requestOptions).map(this.extractData);
        this.reset();
        return observable;
    }

    delete(data: Object): Observable<any> {
        let requestOptions = new RequestOptions({
            headers: this.getHeader(),
            body: data
        });
        let model = this.className.replace('.', '-');
        let url = this.toURL(undefined);
        // let observable = this.http.request(url, requestOptions).map(this.extractData);
        let observable = this.http.delete(url, requestOptions).map(this.extractData);
        this.reset();
        return observable;
    }

    ajax(): Observable<any> {
        let requestOptions =  this.getRequestHeader();
        let url = this.toURL(undefined);
        let http = this.method == 'GET' ? this.http.get(url, requestOptions) : this.http.get(url);

        let observable = http.map(this.extractData);
        this.reset();
        return observable;
    }

    private extractData(res: Response) {
        let body = res.json();
        return body || {};
    }

    private errorHandler(error: any) {
        let msg = (error.message) ? error.message :
            error.status ? `${error.status} - ${error.statusText}` : 'Server error';
        return Observable.throw(msg);
    }
}

// Usage Example
/*
let UsersTable = dataService.init('SecurityUsers');
let wc = UsersTable.wildcard();
let url = UsersTable
    .select(['id', 'name'])
    .contain(['Genders'])
    .find('NotAdmin')
    .find('ByGender', {'gender': 'M'})
    .where({'id': 1})
    .orWhere({
        'first_name': wc + 'aa' + wc,
        'last_name': wc + 'ste' + wc
    })
    .group(['id'])
    .order(['name'])
    .limit(30)
    .page(1)
    .toURL();
*/
