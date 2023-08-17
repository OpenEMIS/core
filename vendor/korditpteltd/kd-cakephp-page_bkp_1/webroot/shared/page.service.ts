import { Injectable } from '@angular/core';
import { Http, Response, Headers, RequestOptions, RequestMethod } from '@angular/http';
import 'rxjs/add/operator/map';
import { Observable } from 'rxjs/Observable';

@Injectable()
export class PageService {
    private base: string;
    private action: string = 'index';
    private controller: string;
    private responseType: string = 'json';
    private search: string = '';
    private querystringValue: any = '';
    private limit: number = null;
    private page: number = null;
    private sort: string;
    private direction: string;

    constructor(private http: Http) {}

    init(controllerName: string, action: string = 'index') {
        let ds = new PageService(this.http);
        // set base to config file
        ds.setBase(this.base);
        ds.setControllerName(controllerName);
        ds.setAction(action);
        return ds;
    }

    setBase(base: string) {
        this.base = base;
    }

    setControllerName(controller: string) {
        this.controller = controller;
    }

    setAction(action: string) {
        this.action = action;
    }

    reset() {
        this.limit = null;
        this.page = null;
        this.querystringValue = '';
    }

    querystring(key: string, value:string) {
        if (value != null && value.trim().length == 0) {
            return;
        }
        var querystringValue = this.querystringValue;

        if (querystringValue != null) {
            querystringValue = JSON.parse(this.hexDecode(querystringValue));
        } else {
            querystringValue = {};
        }

        if (value == null) {
            delete querystringValue[key];
        } else {
            querystringValue[key] = value;
        }

        var count = 0;
        for(var prop in querystringValue) {
            if(querystringValue.hasOwnProperty(prop)) ++count;
        }
        if (count > 0) {
            querystringValue = this.hexEncode(JSON.stringify(querystringValue));
            this.querystringValue = querystringValue;
        } else {
            this.querystringValue = '';
        }
    }

    setQueryString(obj: any) {
        this.querystringValue = this.hexEncode(JSON.stringify(obj));
        return this;
    }

    hexEncode(textStr: string) {
        let result = "";
        let hex = "";
        for (let i=0; i < textStr.length; i++) {
            hex = textStr.charCodeAt(i).toString(16);
            result += ("000"+hex).slice(-4);
        }
        return result;
    }

    hexDecode(hexStr: string) {
        let hexes = hexStr.match(/.{1,4}/g) || [];
        let result = "";
        for(let i = 0; i < hexes.length; i++) {
            result += String.fromCharCode(parseInt(hexes[i], 16));
        }
        return result;
    }

    setLimit(limit: number) {
        this.limit = limit;
        return this;
    }

    setPage(page: number) {
        this.page = page;
        return this;
    }

    sortFields(field: string, direction: string = 'asc') {
        this.sort = field;
        this.direction = direction;
        return this;
    }

    toURL(action: string, primaryKey: string = null): string {
        let url = [this.base, this.controller, action].join('/');

        let params = [];

        if (this.querystringValue.length > 0) {
            params.push('querystring=' + this.querystringValue);
        }

        if (this.limit != null) {
            params.push('limit=' + this.limit);
        }

        if (this.page != null) {
            params.push('page=' + this.page);
        }

        if (primaryKey != null && typeof primaryKey == 'string') {
            url += '/' + primaryKey;
        }

        if (this.sort != undefined && this.direction != undefined) {
            params.push('sort=' + this.sort);
            params.push('direction=' + this.direction);
        }

        url += '.' + this.responseType;

        if (params.length > 0) {
            url += '?' + params.join('&');
        }

        return url;
    }

    getAll(primaryKey: string = null): Observable<any> {
        let action = this.action;
        let url = this.toURL(action, primaryKey);
        let http = this.http.get(url);
        let observable = http.map(this.extractData);
        this.reset();
        return observable;
    }

    save(data: Object): Observable<any> {
        let url = this.toURL('add');
        let observable = this.http.post(url, data).map(this.extractData);
        this.reset();
        return observable;
    }

    edit(primaryKey: string, data: Object): Observable<any> {
        let url = this.toURL('edit', primaryKey);
        let observable = this.http.patch(url, data).map(this.extractData);
        this.reset();
        return observable;
    }

    delete(primaryKey: string, data: Object): Observable<any> {
        let requestOptions = new RequestOptions({
            body: data
        });
        let url = this.toURL('delete', primaryKey);
        // let observable = this.http.request(url, requestOptions).map(this.extractData);
        let observable = this.http.delete(url, requestOptions).map(this.extractData);
        this.reset();
        return observable;
    }

    onChange(uri: string, data: Object = {}) {
        this.setQueryString(data);
        let url = this.toURL('onchange', uri);
        let observable = this.http.get(url).map(this.extractData);
        this.reset();
        return observable;
    }

    private extractData(res: Response) {
        let body = res.json();
        return body || {};
    }
}
